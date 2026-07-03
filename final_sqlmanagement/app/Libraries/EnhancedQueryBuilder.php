<?php

namespace App\Libraries;

class EnhancedQueryBuilder extends DynamicQueryBuilder
{
    /**
     * Build enhanced SQL query with complex ORDER BY and GROUP BY
     */
    public function buildEnhancedQuery($report, $parameters = [])
    {
        $this->report = $report;
        $this->parameters = $parameters;
        
        $sql = $this->buildSelect();
        $sql .= $this->buildFrom();
        $sql .= $this->buildEnhancedJoins();
        $sql .= $this->buildEnhancedWhere();
        $sql .= $this->buildEnhancedGroupBy();
        $sql .= $this->buildEnhancedHaving();
        $sql .= $this->buildEnhancedOrderBy();
        
        return [
            'sql' => $sql,
            'params' => $this->extractEnhancedParameters()
        ];
    }
    
    /**
     * Build enhanced JOINs with subqueries and parameter binding
     */
    protected function buildEnhancedJoins()
    {
        $joins = [];
        $tableIndex = 1;
        
        foreach ($this->report['joins'] as $join) {
            $joinType = strtoupper($join['join_type']);
            $tableName = $this->db->escapeIdentifiers($join['table_name']);
            $alias = $join['alias'] ? $this->db->escapeIdentifiers($join['alias']) : "t{$tableIndex}";
            $condition = $this->processJoinCondition($join['join_condition'], $join);
            
            if ($join['is_subquery'] && !empty($join['subquery_sql'])) {
                $subquery = $this->processParameterizedSql($join['subquery_sql'], $join);
                $tableName = "({$subquery})";
            }
            
            $joins[] = "{$joinType} JOIN {$tableName} AS {$alias} ON {$condition}";
            $tableIndex++;
        }
        
        return empty($joins) ? '' : "\n" . implode("\n", $joins);
    }
    
    /**
     * Build enhanced WHERE with complex conditions
     */
    protected function buildEnhancedWhere()
    {
        $whereConditions = [];
        
        foreach ($this->report['conditions'] as $condition) {
            $processedCondition = $this->processCondition($condition);
            if ($processedCondition) {
                $whereConditions[] = [
                    'condition' => $processedCondition,
                    'operator' => $condition['operator'] ?? 'AND',
                    'group' => $condition['condition_group'] ?? 0
                ];
            }
        }
        
        if (empty($whereConditions)) {
            return '';
        }
        
        return "\nWHERE " . $this->buildConditionGroup($whereConditions);
    }
    
    /**
     * Build enhanced GROUP BY with ROLLUP, CUBE, GROUPING SETS
     */
    protected function buildEnhancedGroupBy()
    {
        if (empty($this->report['groups'])) {
            return '';
        }
        
        $groups = [];
        $hasRollup = false;
        $hasCube = false;
        $hasGroupingSets = false;
        
        foreach ($this->report['groups'] as $group) {
            $groupType = $group['group_type'] ?? 'COLUMN';
            $expression = $this->processExpression($group['group_expression']);
            
            switch ($groupType) {
                case 'ROLLUP':
                    $hasRollup = true;
                    $groups[] = $expression;
                    break;
                case 'CUBE':
                    $hasCube = true;
                    $groups[] = $expression;
                    break;
                case 'GROUPING_SETS':
                    $hasGroupingSets = true;
                    $groups[] = $expression;
                    break;
                default:
                    $groups[] = $expression;
            }
            
            if ($group['with_rollup'] ?? false) {
                $hasRollup = true;
            }
        }
        
        if (empty($groups)) {
            return '';
        }
        
        $groupBy = "\nGROUP BY " . implode(", ", $groups);
        
        if ($hasRollup) {
            $groupBy .= " WITH ROLLUP";
        } elseif ($hasCube) {
            $groupBy .= " WITH CUBE";
        } elseif ($hasGroupingSets) {
            $groupBy .= " GROUPING SETS (" . implode(", ", $groups) . ")";
        }
        
        return $groupBy;
    }
    
    /**
     * Build enhanced HAVING clause
     */
    protected function buildEnhancedHaving()
    {
        if (empty($this->report['having'])) {
            return '';
        }
        
        $havingConditions = [];
        
        foreach ($this->report['having'] as $having) {
            $expression = $this->processExpression($having['having_expression']);
            $havingConditions[] = [
                'condition' => $expression,
                'operator' => $having['operator'] ?? 'AND'
            ];
        }
        
        $havingClause = "\nHAVING " . $havingConditions[0]['condition'];
        
        for ($i = 1; $i < count($havingConditions); $i++) {
            $havingClause .= " {$havingConditions[$i]['operator']} {$havingConditions[$i]['condition']}";
        }
        
        return $havingClause;
    }
    
    /**
     * Build enhanced ORDER BY with NULLS FIRST/LAST, expressions
     */
    protected function buildEnhancedOrderBy()
    {
        if (empty($this->report['orders'])) {
            return '';
        }
        
        $orders = [];
        
        foreach ($this->report['orders'] as $order) {
            $orderType = $order['order_type'] ?? 'COLUMN';
            $expression = $this->processExpression($order['order_expression']);
            $direction = strtoupper($order['direction'] ?? 'ASC');
            $nullsOrder = $order['nulls_order'] ?? null;
            
            $orderClause = $expression . " " . $direction;
            
            if ($nullsOrder) {
                $orderClause .= " " . $nullsOrder;
            }
            
            $orders[] = $orderClause;
        }
        
        return "\nORDER BY " . implode(", ", $orders);
    }
    
    /**
     * Process complex expressions with parameters
     */
    protected function processExpression($expression)
    {
        // Handle CASE WHEN mappings
        if (strpos($expression, 'CASE_FIELD:') === 0) {
            $fieldName = substr($expression, 11);
            return $this->buildCaseExpression($fieldName);
        }
        
        // Handle parameter placeholders
        return preg_replace_callback('/:(\w+)/', function($matches) {
            $paramName = $matches[1];
            if (isset($this->parameters[$paramName])) {
                return $this->db->escape($this->parameters[$paramName]);
            }
            return 'NULL';
        }, $expression);
    }
    
    /**
     * Build CASE WHEN expression from mappings
     */
    protected function buildCaseExpression($fieldName)
    {
        if (empty($this->report['case_mappings'])) {
            return $fieldName;
        }
        
        $caseSQL = "CASE";
        
        foreach ($this->report['case_mappings'] as $case) {
            if ($case['case_field'] === $fieldName) {
                $caseSQL .= " WHEN {$case['when_expression']} THEN '{$case['then_value']}'";
            }
        }
        
        // Add ELSE from first matching case
        foreach ($this->report['case_mappings'] as $case) {
            if ($case['case_field'] === $fieldName && !empty($case['else_value'])) {
                $caseSQL .= " ELSE '{$case['else_value']}'";
                break;
            }
        }
        
        $caseSQL .= " END";
        
        return $caseSQL;
    }
    
    /**
     * Process JOIN conditions with parameter binding
     */
    protected function processJoinCondition($condition, $join)
    {
        if (!empty($join['parameter_bindings'])) {
            $bindings = json_decode($join['parameter_bindings'], true);
            foreach ($bindings as $placeholder => $paramName) {
                if (isset($this->parameters[$paramName])) {
                    $condition = str_replace(":{$placeholder}", $this->db->escape($this->parameters[$paramName]), $condition);
                }
            }
        }
        
        return $condition;
    }
    
    /**
     * Process SQL with parameters
     */
    protected function processParameterizedSql($sql, $context)
    {
        return preg_replace_callback('/:(\w+)/', function($matches) use ($context) {
            $paramName = $matches[1];
            
            // Check context-specific parameters
            if (!empty($context['parameter_bindings'])) {
                $bindings = json_decode($context['parameter_bindings'], true);
                if (isset($bindings[$paramName])) {
                    $actualParam = $bindings[$paramName];
                    if (isset($this->parameters[$actualParam])) {
                        return $this->db->escape($this->parameters[$actualParam]);
                    }
                }
            }
            
            // Check global parameters
            if (isset($this->parameters[$paramName])) {
                return $this->db->escape($this->parameters[$paramName]);
            }
            
            return 'NULL';
        }, $sql);
    }
    
    /**
     * Process condition with parameters and value sources
     */
    protected function processCondition($condition)
    {
        $expression = $condition['condition_expression'];
        
        // Handle parameterized conditions
        if ($condition['is_parameter']) {
            $paramName = $condition['parameter_name'];
            if (isset($this->parameters[$paramName])) {
                $value = $this->parameters[$paramName];
                
                // Handle different value types
                switch ($condition['value_type'] ?? 'static') {
                    case 'array':
                        if (is_array($value)) {
                            $valueList = implode(',', array_map([$this->db, 'escape'], $value));
                            $expression = str_replace('?', $valueList, $expression);
                        }
                        break;
                    case 'date_range':
                        if (is_array($value) && isset($value['start']) && isset($value['end'])) {
                            $expression = str_replace(
                                ['?start', '?end'],
                                [$this->db->escape($value['start']), $this->db->escape($value['end'])],
                                $expression
                            );
                        }
                        break;
                    default:
                        $expression = str_replace('?', $this->db->escape($value), $expression);
                }
            } elseif ($condition['parameter_default']) {
                $expression = str_replace('?', $this->db->escape($condition['parameter_default']), $expression);
            }
        }
        
        // Handle value sources
        if (!empty($condition['value_source'])) {
            $expression = $this->processValueSource($expression, $condition['value_source']);
        }
        
        return $expression;
    }
    
    /**
     * Process value source (subquery, function, etc.)
     */
    protected function processValueSource($expression, $valueSource)
    {
        switch ($valueSource['type']) {
            case 'subquery':
                $subquery = $this->processParameterizedSql($valueSource['sql'], $valueSource);
                return str_replace('?', "({$subquery})", $expression);
            case 'function':
                $funcResult = call_user_func($valueSource['function'], $this->parameters);
                return str_replace('?', $this->db->escape($funcResult), $expression);
            default:
                return $expression;
        }
    }
    
    /**
     * Build condition groups with parentheses
     */
    protected function buildConditionGroup($conditions)
    {
        $grouped = [];
        $currentGroup = [];
        $currentGroupId = 0;
        
        foreach ($conditions as $condition) {
            $group = $condition['group'];
            
            if ($group != $currentGroupId && !empty($currentGroup)) {
                if (count($currentGroup) > 1) {
                    $grouped[] = "(" . implode(" {$currentGroup[0]['operator']} ", array_column($currentGroup, 'condition')) . ")";
                } else {
                    $grouped[] = $currentGroup[0]['condition'];
                }
                $currentGroup = [];
            }
            
            $currentGroup[] = $condition;
            $currentGroupId = $group;
        }
        
        // Add last group
        if (!empty($currentGroup)) {
            if (count($currentGroup) > 1) {
                $grouped[] = "(" . implode(" {$currentGroup[0]['operator']} ", array_column($currentGroup, 'condition')) . ")";
            } else {
                $grouped[] = $currentGroup[0]['condition'];
            }
        }
        
        return implode(" AND ", $grouped);
    }
    
    /**
     * Extract enhanced parameters
     */
    protected function extractEnhancedParameters()
    {
        $params = [];
        
        // Extract from conditions
        foreach ($this->report['conditions'] as $condition) {
            if ($condition['is_parameter']) {
                $params[$condition['parameter_name']] = [
                    'name' => $condition['parameter_name'],
                    'default' => $condition['parameter_default'],
                    'type' => $condition['value_type'] ?? 'static'
                ];
            }
        }
        
        // Extract from joins
        foreach ($this->report['joins'] as $join) {
            if ($join['is_parameterized'] && !empty($join['parameter_bindings'])) {
                $bindings = json_decode($join['parameter_bindings'], true);
                foreach ($bindings as $placeholder => $paramName) {
                    $params[$paramName] = [
                        'name' => $paramName,
                        'source' => 'join',
                        'placeholder' => $placeholder
                    ];
                }
            }
        }
        
        return array_values($params);
    }
    
    /**
     * Build window functions for complex analytics
     */
    public function buildWindowFunction($function, $partitionBy = [], $orderBy = [], $frame = null)
    {
        $sql = $function;
        
        if (!empty($partitionBy) || !empty($orderBy)) {
            $sql .= " OVER (";
            
            if (!empty($partitionBy)) {
                $sql .= "PARTITION BY " . implode(", ", $partitionBy);
            }
            
            if (!empty($orderBy)) {
                if (!empty($partitionBy)) {
                    $sql .= " ";
                }
                $sql .= "ORDER BY " . implode(", ", $orderBy);
            }
            
            if ($frame) {
                $sql .= " " . $frame;
            }
            
            $sql .= ")";
        }
        
        return $sql;
    }
    
    /**
     * Build recursive CTE
     */
    public function buildRecursiveCTE($cteName, $anchorQuery, $recursiveQuery, $columns = [])
    {
        $sql = "WITH RECURSIVE {$cteName}";
        
        if (!empty($columns)) {
            $sql .= "(" . implode(", ", $columns) . ")";
        }
        
        $sql .= " AS (\n";
        $sql .= "  {$anchorQuery}\n";
        $sql .= "  UNION ALL\n";
        $sql .= "  {$recursiveQuery}\n";
        $sql .= ")";
        
        return $sql;
    }
}