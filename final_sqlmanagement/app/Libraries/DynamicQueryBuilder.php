<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseBuilder;

class DynamicQueryBuilder
{
    protected $db;
    protected $report;
    
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    
    /**
     * Build complete SQL query from report definition
     */
    public function buildQuery($report)
    {
        $this->report = $report;
        
        $sql = $this->buildSelect();
        $sql .= $this->buildFrom();
        $sql .= $this->buildJoins();
        $sql .= $this->buildWhere();
        $sql .= $this->buildGroupBy();
        $sql .= $this->buildHaving();
        $sql .= $this->buildOrderBy();
        
        return [
            'sql' => $sql,
            'params' => $this->extractParameters()
        ];
    }
    
    /**
     * Build SELECT clause
     */
    // protected function buildSelect()
    // {
    //     $selects = [];
        
    //     foreach ($this->report['columns'] as $column) {
    //         // if (!$column['is_visible']) {
    //         //     continue;
    //         // }
            
    //         $expression = $column['column_expression'];
    //         $alias = $this->db->escapeIdentifiers($column['alias']);
            
    //         if ($column['aggregate_function']) {
    //             $expression = "{$column['aggregate_function']}({$expression})";
    //         }
            
    //         $selects[] = "{$expression} AS {$alias}";
    //     }
        
    //     if (empty($selects)) {
    //         $selects[] = "*";
    //     }
        
    //     return "SELECT " . implode(", ", $selects);
    // }

    protected function buildSelect()
    {
        $selects = [];
        
        foreach ($this->report['columns'] as $column) {
            if (isset($column['is_visible']) && !$column['is_visible']) {
                continue;
            }
            
            $expression = $column['column_expression'] ?? '';
            $alias = $column['alias'] ?? '';
            
            if (empty($expression)) {
                continue;
            }
            
            if ($column['aggregate_function'] ?? false) {
                $expression = "{$column['aggregate_function']}({$expression})";
            }
            
            if (!empty($alias)) {
                $alias = $this->db->escapeIdentifiers($alias);
                $selects[] = "{$expression} AS {$alias}";
            } else {
                $selects[] = $expression;
            }
        }
        
        if (empty($selects)) {
            $selects[] = "*";
        }
        
        return "SELECT " . implode(", ", $selects);
    }
    
    /**
     * Build FROM clause
     */
    protected function buildFrom()
    {
        $baseTable = $this->db->escapeIdentifiers($this->report['base_table']);
        $alias = 't0'; // Default alias for base table
        
        return "\nFROM {$baseTable} AS {$alias}";
    }
    
    /**
     * Build JOIN clauses
     */
    // protected function buildJoins()
    // {
    //     $joins = [];
    //     $tableIndex = 1;
        
    //     foreach ($this->report['joins'] as $join) {
    //         $joinType = strtoupper($join['join_type']);
    //         $tableName = $this->db->escapeIdentifiers($join['table_name']);
    //         $alias = $join['alias'] ? $this->db->escapeIdentifiers($join['alias']) : "t{$tableIndex}";
    //         $condition = $join['join_condition'];
            
    //         if ($join['is_subquery'] && !empty($join['subquery_sql'])) {
    //             $tableName = "({$join['subquery_sql']})";
    //         }
            
    //         $joins[] = "{$joinType} JOIN {$tableName} AS {$alias} ON {$condition}";
    //         $tableIndex++;
    //     }
        
    //     return empty($joins) ? '' : "\n" . implode("\n", $joins);
    // }

    protected function buildJoins()
    {
        $joins = [];
        $tableIndex = 1;
        
        foreach ($this->report['joins'] as $join) {
            $joinType = strtoupper($join['join_type'] ?? 'INNER');
            $tableName = $join['table_name'] ?? '';
            $alias = $join['alias'] ?? "t{$tableIndex}";
            $condition = $join['join_condition'] ?? '';
            
            if (empty($tableName) || empty($condition)) {
                continue;
            }
            
            $tableName = $this->db->escapeIdentifiers($tableName);
            $alias = $this->db->escapeIdentifiers($alias);
            
            $joins[] = "{$joinType} JOIN {$tableName} AS {$alias} ON {$condition}";
            $tableIndex++;
        }
        
        return empty($joins) ? '' : "\n" . implode("\n", $joins);
    }
    
    /**
     * Build WHERE clause
     */
    // protected function buildWhere()
    // {
    //     $whereConditions = [];
    //     $havingConditions = [];
        
    //     foreach ($this->report['conditions'] as $condition) {
    //         if ($condition['condition_type'] === 'HAVING') {
    //             $havingConditions[] = $this->buildCondition($condition);
    //         } else {
    //             $whereConditions[] = $this->buildCondition($condition);
    //         }
    //     }
        
    //     $whereSQL = '';
    //     if (!empty($whereConditions)) {
    //         $whereSQL = "\nWHERE " . $this->groupConditions($whereConditions);
    //     }
        
    //     $this->havingConditions = $havingConditions;
        
    //     return $whereSQL;
    // }

    protected function buildWhere()
    {
        $whereConditions = [];
        $havingConditions = [];
        
        foreach ($this->report['conditions'] as $condition) {
            $conditionType = $condition['condition_type'] ?? 'WHERE';
            $conditionExpression = $condition['condition_expression'] ?? '';
            
            if (empty($conditionExpression)) {
                continue;
            }
            
            if ($conditionType === 'HAVING') {
                $havingConditions[] = $this->buildCondition($condition);
            } else {
                $whereConditions[] = $this->buildCondition($condition);
            }
        }
        
        $whereSQL = '';
        if (!empty($whereConditions)) {
            $whereSQL = "\nWHERE " . $this->groupConditions($whereConditions);
        }
        
        $this->havingConditions = $havingConditions;
        
        return $whereSQL;
    }
    
    /**
     * Build HAVING clause
     */
    // protected function buildHaving()
    // {
    //     if (empty($this->havingConditions)) {
    //         return '';
    //     }
        
    //     return "\nHAVING " . $this->groupConditions($this->havingConditions);
    // }

    protected function buildHaving()
    {
        if (empty($this->havingConditions)) {
            return '';
        }
        
        return "\nHAVING " . $this->groupConditions($this->havingConditions);
    }
    
    /**
     * Build GROUP BY clause
     */
    // protected function buildGroupBy()
    // {
    //     if (empty($this->report['groups'])) {
    //         return '';
    //     }
        
    //     $groups = [];
    //     foreach ($this->report['groups'] as $group) {
    //         $groups[] = $group['group_column'];
    //     }
        
    //     return "\nGROUP BY " . implode(", ", $groups);
    // }

    protected function buildGroupBy()
    {
        if (empty($this->report['groups'])) {
            return '';
        }
        
        $groups = [];
        foreach ($this->report['groups'] as $group) {
            // Use 'group_expression' instead of 'group_column'
            $expression = $group['group_expression'] ?? ($group['group_column'] ?? '');
            
            if (!empty($expression)) {
                $groups[] = $expression;
            }
        }
        
        if (empty($groups)) {
            return '';
        }
        
        return "\nGROUP BY " . implode(", ", $groups);
    }
    
    /**
     * Build ORDER BY clause
     */
    // protected function buildOrderBy()
    // {
    //     if (empty($this->report['orders'])) {
    //         return '';
    //     }
        
    //     $orders = [];
    //     foreach ($this->report['orders'] as $order) {
    //         $direction = strtoupper($order['direction']);
    //         $orders[] = "{$order['order_column']} {$direction}";
    //     }
        
    //     return "\nORDER BY " . implode(", ", $orders);
    // }

    protected function buildOrderBy()
    {
        if (empty($this->report['orders'])) {
            return '';
        }
        
        $orders = [];
        foreach ($this->report['orders'] as $order) {
            // Use 'order_expression' instead of 'order_column'
            $expression = $order['order_expression'] ?? ($order['order_column'] ?? '');
            $direction = strtoupper($order['direction'] ?? 'ASC');
            
            if (!empty($expression)) {
                $orders[] = "{$expression} {$direction}";
            }
        }
        
        if (empty($orders)) {
            return '';
        }
        
        return "\nORDER BY " . implode(", ", $orders);
    }
    
    /**
     * Build individual condition
     */
    // protected function buildCondition($condition)
    // {
    //     $expression = $condition['condition_expression'];
    //     $operator = $condition['operator'] ?? 'AND';
        
    //     // Handle special condition types
    //     switch ($condition['condition_type']) {
    //         case 'EXISTS':
    //             return "EXISTS ({$expression})";
    //         case 'NOT EXISTS':
    //             return "NOT EXISTS ({$expression})";
    //         case 'IN':
    //         case 'NOT IN':
    //             return "{$expression} {$condition['condition_type']} (?)";
    //         default:
    //             return $expression;
    //     }
    // }

    protected function buildCondition($condition)
    {
        $expression = $condition['condition_expression'] ?? '';
        $operator = $condition['operator'] ?? 'AND';
        
        // Handle special condition types
        switch ($condition['condition_type'] ?? 'WHERE') {
            case 'EXISTS':
                return "EXISTS ({$expression})";
            case 'NOT EXISTS':
                return "NOT EXISTS ({$expression})";
            case 'IN':
            case 'NOT IN':
                return "{$expression} {$condition['condition_type']} (?)";
            default:
                return $expression;
        }
    }
    
    /**
     * Group conditions with parentheses
     */
    protected function groupConditions($conditions)
    {
        $grouped = [];
        $currentGroup = [];
        $currentGroupId = 0;
        
        foreach ($conditions as $condition) {
            $group = isset($condition['condition_group']) ? $condition['condition_group'] : 0;
            
            if ($group != $currentGroupId && !empty($currentGroup)) {
                if (count($currentGroup) > 1) {
                    $grouped[] = "(" . implode(" {$currentGroup[0]['operator']} ", array_column($currentGroup, 'condition')) . ")";
                } else {
                    $grouped[] = $currentGroup[0]['condition'];
                }
                $currentGroup = [];
            }
            
            $currentGroup[] = [
                'condition' => $condition,
                'operator' => $condition['operator'] ?? 'AND'
            ];
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
     * Extract parameters from conditions
     */
    protected function extractParameters()
    {
        $params = [];
        
        foreach ($this->report['conditions'] as $condition) {
            if ($condition['is_parameter']) {
                $params[] = [
                    'name' => $condition['parameter_name'],
                    'default' => $condition['parameter_default']
                ];
            }
        }
        
        return $params;
    }
    
    /**
     * Build complex CASE WHEN expression dynamically
     */
    public function buildCaseWhenExpression($cases, $else = null)
    {
        $caseSQL = "CASE";
        
        foreach ($cases as $when => $then) {
            $caseSQL .= " WHEN {$when} THEN '{$then}'";
        }
        
        if ($else !== null) {
            $caseSQL .= " ELSE '{$else}'";
        }
        
        $caseSQL .= " END";
        
        return $caseSQL;
    }
    
    /**
     * Build subquery expression
     */
    public function buildSubquery($sql, $alias = null)
    {
        $subquery = "({$sql})";
        
        if ($alias) {
            $subquery .= " AS {$alias}";
        }
        
        return $subquery;
    }
}