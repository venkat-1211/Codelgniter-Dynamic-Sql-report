<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;

class DynamicQueryBuilder
{
    protected $db;
    protected $queryBuilder;
    protected $reportDefinition;
    protected $runtimeParameters = [];
    protected $useQueryBuilder = true;
    protected $rawSqlMode = false;
    protected $parameterizedConditions = [];

    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
        $this->queryBuilder = null;
    }

    /**
     * Set report definition
     */
    public function setReportDefinition(array $definition): self
    {
        $this->reportDefinition = $definition;
        return $this;
    }

    /**
     * Set runtime parameters
     */
    public function setParameters(array $parameters): self
    {
        $this->runtimeParameters = $parameters;
        return $this;
    }

    /**
     * Enable raw SQL mode for complex queries
     */
    public function enableRawSqlMode(): self
    {
        $this->rawSqlMode = true;
        $this->useQueryBuilder = false;
        return $this;
    }

    /**
     * Build and execute the report query
     */
    public function execute(array $options = []): array
    {
        if ($this->rawSqlMode) {
            return $this->executeRawSql($options);
        }

        return $this->executeQueryBuilder($options);
    }

    /**
     * Execute using Query Builder for simpler queries
     */
    protected function executeQueryBuilder(array $options): array
    {
        // Initialize queryBuilder with the base table
        $this->queryBuilder = $this->db->table($this->reportDefinition['base_table']);
        
        // Apply joins
        $this->applyJoins();
    
        // Select columns
        $this->applyColumns();
    
        // Apply WHERE conditions
        $this->applyConditions('WHERE');
    
        // Apply GROUP BY
        $this->applyGroups();
    
        // Apply HAVING conditions
        $this->applyConditions('HAVING');
    
        // Apply EXISTS/NOT EXISTS conditions
        $this->applyExistsConditions();
    
        // Apply ORDER BY
        $this->applyOrders();
    
        // Apply pagination
        if (!empty($options['limit'])) {
            $offset = $options['offset'] ?? 0;
            $this->queryBuilder->limit($options['limit'], $offset);
        }
    
        $query = $this->queryBuilder->get();
        return $query->getResultArray();
    }

    /**
     * Execute using Raw SQL for complex queries
     */
    protected function executeRawSql(array $options): array
    {
        $sql = $this->buildRawSql($options);
        
        // Bind parameters if any
        if (!empty($this->parameterizedConditions)) {
            $query = $this->db->query($sql, $this->parameterizedConditions);
        } else {
            $query = $this->db->query($sql);
        }

        return $query->getResultArray();
    }

    /**
     * Build raw SQL from report definition
     */
    public function buildRawSql(array $options = []): string
    {
        $select = $this->buildSelectClause();
        $from = $this->reportDefinition['base_table'];
        $joins = $this->buildJoinClause();
        $where = $this->buildWhereClause();
        $groupBy = $this->buildGroupByClause();
        $having = $this->buildHavingClause();
        $orderBy = $this->buildOrderByClause();
        
        $sql = "SELECT {$select} FROM {$from}";
        
        if ($joins) {
            $sql .= " {$joins}";
        }
        
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        if ($groupBy) {
            $sql .= " GROUP BY {$groupBy}";
        }
        
        if ($having) {
            $sql .= " HAVING {$having}";
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        // Apply pagination
        if (!empty($options['limit'])) {
            $offset = $options['offset'] ?? 0;
            $sql .= " LIMIT {$offset}, {$options['limit']}";
        }
        
        return $sql;
    }

    /**
     * Build SELECT clause
     */
    protected function buildSelectClause(): string
    {
        $columns = [];
        foreach ($this->reportDefinition['columns'] ?? [] as $column) {
            $expression = $this->resolveExpression($column['column_expression']);
            $alias = $this->db->escapeIdentifiers($column['alias']);
            $columns[] = "{$expression} AS {$alias}";
        }
        
        return empty($columns) ? '*' : implode(', ', $columns);
    }

    /**
     * Build JOIN clause
     */
    protected function buildJoinClause(): string
    {
        $joins = [];
        foreach ($this->reportDefinition['joins'] ?? [] as $join) {
            $type = strtoupper($join['join_type']);
            $table = $this->db->escapeIdentifiers($join['table_name']);
            $alias = $join['table_alias'] ? $this->db->escapeIdentifiers($join['table_alias']) : '';
            $condition = $this->resolveExpression($join['join_condition']);
            
            $joinClause = "{$type} JOIN {$table}";
            if ($alias) {
                $joinClause .= " AS {$alias}";
            }
            $joinClause .= " ON {$condition}";
            
            $joins[] = $joinClause;
        }
        
        return implode(' ', $joins);
    }

    /**
     * Build WHERE clause
     */
    protected function buildWhereClause(): string
    {
        return $this->buildConditionClause('WHERE');
    }

    /**
     * Build HAVING clause
     */
    protected function buildHavingClause(): string
    {
        return $this->buildConditionClause('HAVING');
    }

    /**
     * Build condition clause
     */
    protected function buildConditionClause(string $type): string
    {
        $conditions = [];
        
        foreach ($this->reportDefinition['filters'] ?? [] as $filter) {
            if ($filter['condition_type'] !== $type) {
                continue;
            }
            
            $expression = $this->resolveExpression($filter['condition_expression']);
            
            // Handle parameterized conditions
            if (!empty($filter['parameter_name']) && isset($this->runtimeParameters[$filter['parameter_name']])) {
                $value = $this->runtimeParameters[$filter['parameter_name']];
                $expression = $this->applyParameterValue($expression, $value, $filter['parameter_type'] ?? 'string');
            }
            
            $conditions[] = $expression;
        }
        
        return empty($conditions) ? '' : implode(' AND ', $conditions);
    }

    /**
     * Apply parameter value to condition
     */
    protected function applyParameterValue(string $expression, $value, string $type): string
    {
        if ($type === 'array' && is_array($value)) {
            // Handle IN clause
            $escapedValues = array_map([$this->db, 'escape'], $value);
            return str_replace(':value', '(' . implode(', ', $escapedValues) . ')', $expression);
        } elseif ($type === 'date' || $type === 'datetime') {
            // Handle date ranges
            if (is_array($value) && isset($value['from'], $value['to'])) {
                $from = $this->db->escape($value['from']);
                $to = $this->db->escape($value['to']);
                return str_replace(':value', "BETWEEN {$from} AND {$to}", $expression);
            }
        }
        
        // Default: single value
        $escapedValue = $this->db->escape($value);
        return str_replace(':value', $escapedValue, $expression);
    }

    /**
     * Build GROUP BY clause
     */
    protected function buildGroupByClause(): string
    {
        $groups = [];
        foreach ($this->reportDefinition['groups'] ?? [] as $group) {
            $groups[] = $this->db->escapeIdentifiers($group['column_alias']);
        }
        
        return implode(', ', $groups);
    }

    /**
     * Build ORDER BY clause
     */
    protected function buildOrderByClause(): string
    {
        $orders = [];
        foreach ($this->reportDefinition['orders'] ?? [] as $order) {
            $column = $this->db->escapeIdentifiers($order['column_alias']);
            $direction = strtoupper($order['direction']);
            $orders[] = "{$column} {$direction}";
        }
        
        return empty($orders) ? '1' : implode(', ', $orders);
    }

    /**
     * Apply joins to Query Builder
     */
    protected function applyJoins(): void
    {
        if (empty($this->reportDefinition['joins'])) {
            return;
        }

        // Sort joins by order
        usort($this->reportDefinition['joins'], function($a, $b) {
            return $a['join_order'] <=> $b['join_order'];
        });

        foreach ($this->reportDefinition['joins'] as $join) {
            $condition = $this->resolveExpression($join['join_condition']);
            $method = strtolower($join['join_type']) . 'Join';
            
            if (method_exists($this->queryBuilder, $method)) {
                $this->queryBuilder->{$method}(
                    $join['table_name'] . ($join['table_alias'] ? ' AS ' . $join['table_alias'] : ''),
                    $condition
                );
            }
        }
    }

    /**
     * Apply columns to Query Builder
     */
    protected function applyColumns(): void
    {
        if (empty($this->reportDefinition['columns'])) {
            return;
        }

        foreach ($this->reportDefinition['columns'] as $column) {
            $expression = $this->resolveExpression($column['column_expression']);
            $this->queryBuilder->select("{$expression} AS {$column['alias']}", false);
        }
    }

    /**
     * Apply conditions to Query Builder
     */
    protected function applyConditions(string $type): void
    {
        foreach ($this->reportDefinition['filters'] ?? [] as $filter) {
            if ($filter['condition_type'] !== $type) {
                continue;
            }

            $expression = $this->resolveExpression($filter['condition_expression']);
            
            // Handle runtime parameters
            if (!empty($filter['parameter_name']) && isset($this->runtimeParameters[$filter['parameter_name']])) {
                $value = $this->runtimeParameters[$filter['parameter_name']];
                $this->applyParameterizedCondition($expression, $value, $filter['parameter_type'] ?? 'string');
            } else {
                // Static condition
                $this->queryBuilder->where($expression, null, false);
            }
        }
    }

    /**
     * Apply parameterized condition
     */
    protected function applyParameterizedCondition(string $expression, $value, string $type): void
    {
        if ($type === 'array' && is_array($value)) {
            $this->queryBuilder->whereIn($expression, $value);
        } elseif ($type === 'date' || $type === 'datetime') {
            if (is_array($value) && isset($value['from'], $value['to'])) {
                $this->queryBuilder->where("{$expression} BETWEEN", [$value['from'], $value['to']]);
            } elseif (!empty($value)) {
                $this->queryBuilder->where($expression, $value);
            }
        } else {
            $this->queryBuilder->where($expression, $value);
        }
    }

    /**
     * Apply EXISTS conditions
     */
    protected function applyExistsConditions(): void
    {
        foreach ($this->reportDefinition['filters'] ?? [] as $filter) {
            if (!in_array($filter['condition_type'], ['EXISTS', 'NOT EXISTS'])) {
                continue;
            }

            $expression = $this->resolveExpression($filter['condition_expression']);
            $not = $filter['condition_type'] === 'NOT EXISTS';
            
            $this->queryBuilder->where($not ? "NOT EXISTS ({$expression})" : "EXISTS ({$expression})", null, false);
        }
    }

    /**
     * Apply GROUP BY
     */
    protected function applyGroups(): void
    {
        if (empty($this->reportDefinition['groups'])) {
            return;
        }

        foreach ($this->reportDefinition['groups'] as $group) {
            $this->queryBuilder->groupBy($group['column_alias']);
        }
    }

    /**
     * Apply ORDER BY
     */
    protected function applyOrders(): void
    {
        if (empty($this->reportDefinition['orders'])) {
            return;
        }

        foreach ($this->reportDefinition['orders'] as $order) {
            $this->queryBuilder->orderBy($order['column_alias'], $order['direction']);
        }
    }

    /**
     * Resolve expression (handles placeholders, aliases, etc.)
     */
    protected function resolveExpression(string $expression): string
    {
        // Replace {alias} placeholders with actual table aliases
        if (preg_match_all('/\{(\w+)\}/', $expression, $matches)) {
            foreach ($matches[1] as $alias) {
                $replacement = $this->db->escapeIdentifiers($alias);
                $expression = str_replace("{{$alias}}", $replacement, $expression);
            }
        }
        
        return $expression;
    }

    /**
     * Get count query for pagination
     */
    public function getCount(): int
    {
        if ($this->rawSqlMode) {
            $sql = $this->buildCountSql();
            $result = $this->db->query($sql)->getRowArray();
            return (int) ($result['total'] ?? 0);
        }
    
        // Initialize queryBuilder for count
        $this->queryBuilder = $this->db->table($this->reportDefinition['base_table']);
        $this->applyJoins();
        $this->applyConditions('WHERE');
        $this->applyExistsConditions();
        
        return $this->queryBuilder->countAllResults();
    }

    /**
     * Build count SQL
     */
    protected function buildCountSql(): string
    {
        $from = $this->reportDefinition['base_table'];
        $joins = $this->buildJoinClause();
        $where = $this->buildWhereClause();
        
        $sql = "SELECT COUNT(*) as total FROM {$from}";
        
        if ($joins) {
            $sql .= " {$joins}";
        }
        
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        return $sql;
    }

    /**
     * Get complete SQL string (for debugging)
     */
    public function getSql(): string
    {
        if ($this->rawSqlMode) {
            return $this->buildRawSql();
        }

        return $this->queryBuilder->getCompiledSelect();
    }
}