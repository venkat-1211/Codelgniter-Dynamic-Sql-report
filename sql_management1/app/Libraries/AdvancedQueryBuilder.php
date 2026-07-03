<?php
// app/Libraries/AdvancedQueryBuilder.php

namespace App\Libraries;

use Config\ReportSettings;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;

class AdvancedQueryBuilder
{
    protected $db;
    protected $config;
    protected $queryParts = [];
    protected $params = [];
    protected $subqueries = [];

    public function __construct(BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
        $this->config = config('ReportSettings');
        $this->reset();
    }

    /**
     * Reset query builder
     */
    protected function reset(): void
    {
        $this->queryParts = [
            'select' => [],
            'from' => [],
            'joins' => [],
            'where' => [],
            'group_by' => [],
            'having' => [],
            'order_by' => [],
            'limit' => null,
            'offset' => null
        ];
        $this->params = [];
        $this->subqueries = [];
    }

    /**
     * Build complex query from configuration
     */
    public function buildQuery(array $config): string
    {
        $this->reset();
        
        // Handle different report types
        switch ($config['report_type'] ?? 'simple') {
            case 'custom_sql':
                return $this->buildCustomSQL($config);
            case 'advanced':
                return $this->buildAdvancedQuery($config);
            default:
                return $this->buildSimpleQuery($config);
        }
    }

    /**
     * Build custom SQL query
     */
    protected function buildCustomSQL(array $config): string
    {
        if (empty($config['custom_sql'])) {
            throw new \InvalidArgumentException('Custom SQL is required for custom_sql report type');
        }
        
        $sql = $config['custom_sql'];
        
        // Replace parameters if provided
        if (!empty($config['parameters'])) {
            foreach ($config['parameters'] as $key => $value) {
                if (is_array($value)) {
                    // Handle IN clause parameters
                    $placeholders = implode(', ', array_fill(0, count($value), '?'));
                    $sql = str_replace(":$key", $placeholders, $sql);
                    $this->params = array_merge($this->params, $value);
                } else {
                    $sql = str_replace(":$key", '?', $sql);
                    $this->params[] = $value;
                }
            }
        }
        
        return $sql;
    }

    /**
     * Build advanced query with multiple tables and calculated fields
     */
    protected function buildAdvancedQuery(array $config): string
    {
        // Validate config
        if (empty($config['base_tables'])) {
            throw new \InvalidArgumentException('Base tables are required');
        }
        
        if (empty($config['columns'])) {
            throw new \InvalidArgumentException('At least one column is required');
        }
        
        // Set base tables
        $this->setBaseTables($config['base_tables']);
        
        // Add joins
        if (!empty($config['joins'])) {
            $this->addJoins($config['joins']);
        }
        
        // Add columns
        $this->addColumns($config['columns']);
        
        // Add calculated fields
        if (!empty($config['calculated_fields'])) {
            $this->addCalculatedFields($config['calculated_fields']);
        }
        
        // Add filters
        if (!empty($config['filters'])) {
            $this->addFilters($config['filters']);
        }
        
        // Add grouping
        if (!empty($config['grouping'])) {
            $this->addGrouping($config['grouping']);
        }
        
        // Add sorting
        if (!empty($config['sorting'])) {
            $this->addSorting($config['sorting']);
        }
        
        // Add subqueries
        if (!empty($config['subqueries'])) {
            $this->addSubqueries($config['subqueries']);
        }
        
        // Build and return SQL
        return $this->compileQuery();
    }

    /**
     * Build simple query (single table)
     */
    protected function buildSimpleQuery(array $config): string
    {
        if (empty($config['base_table'])) {
            throw new \InvalidArgumentException('Base table is required');
        }
        
        if (empty($config['columns'])) {
            throw new \InvalidArgumentException('At least one column is required');
        }
        
        // Set single base table
        $this->setBaseTable($config['base_table']);
        
        // Add columns
        $this->addColumns($config['columns']);
        
        // Add filters
        if (!empty($config['filters'])) {
            $this->addFilters($config['filters']);
        }
        
        // Add grouping
        if (!empty($config['grouping'])) {
            $this->addGrouping($config['grouping']);
        }
        
        // Add sorting
        if (!empty($config['sorting'])) {
            $this->addSorting($config['sorting']);
        }
        
        return $this->compileQuery();
    }

    /**
     * Set base tables
     */
    protected function setBaseTables(array $tables): void
    {
        foreach ($tables as $table) {
            if (!isset($this->config->availableTables[$table])) {
                throw new \InvalidArgumentException("Table '{$table}' is not available for reporting");
            }
            
            $this->queryParts['from'][] = $this->db->protectIdentifiers($table);
        }
    }

    /**
     * Set single base table
     */
    protected function setBaseTable(string $table): void
    {
        if (!isset($this->config->availableTables[$table])) {
            throw new \InvalidArgumentException("Table '{$table}' is not available for reporting");
        }
        
        $this->queryParts['from'] = [$this->db->protectIdentifiers($table)];
    }

    /**
     * Add joins to query
     */
    protected function addJoins(array $joins): void
    {
        foreach ($joins as $join) {
            if (empty($join['table']) || empty($join['condition']) || empty($join['type'])) {
                continue;
            }
            
            $table = $this->db->protectIdentifiers($join['table']);
            $condition = $join['condition'];
            $type = strtoupper($join['type']);
            
            if (!in_array($type, array_keys($this->config->joinTypes))) {
                $type = 'INNER';
            }
            
            $this->queryParts['joins'][] = "{$type} JOIN {$table} ON {$condition}";
        }
    }

    /**
     * Add columns to select
     */
    protected function addColumns(array $columns): void
    {
        foreach ($columns as $column) {
            if (empty($column['field'])) {
                continue;
            }
            
            $field = $column['field'];
            $alias = $column['alias'] ?? null;
            $table = $column['table'] ?? null;
            
            $columnSQL = '';
            
            if ($table) {
                $columnSQL = $this->db->protectIdentifiers($table) . '.' . $this->db->protectIdentifiers($field);
            } else {
                $columnSQL = $this->db->protectIdentifiers($field);
            }
            
            if ($alias) {
                $columnSQL .= " AS " . $this->db->escapeIdentifiers($alias);
            }
            
            $this->queryParts['select'][] = $columnSQL;
        }
        
        if (empty($this->queryParts['select'])) {
            throw new \InvalidArgumentException('No valid columns specified');
        }
    }

    /**
     * Add calculated fields
     */
    protected function addCalculatedFields(array $calculatedFields): void
    {
        foreach ($calculatedFields as $field) {
            if (empty($field['expression']) || empty($field['alias'])) {
                continue;
            }
            
            $expression = $field['expression'];
            $alias = $field['alias'];
            
            // Replace parameter placeholders
            if (!empty($field['parameters'])) {
                foreach ($field['parameters'] as $key => $value) {
                    $expression = str_replace("{{$key}}", $this->formatValue($value), $expression);
                }
            }
            
            $this->queryParts['select'][] = "{$expression} AS " . $this->db->escapeIdentifiers($alias);
        }
    }

    /**
     * Add filters to query
     */
    protected function addFilters(array $filters): void
    {
        foreach ($filters as $filter) {
            if (empty($filter['field']) || !isset($filter['operator'])) {
                continue;
            }
            
            $field = $filter['field'];
            $operator = strtoupper($filter['operator']);
            $value = $filter['value'] ?? null;
            $valueType = $filter['value_type'] ?? 'string';
            $table = $filter['table'] ?? null;
            
            if ($table) {
                $fieldIdentified = $this->db->protectIdentifiers($table) . '.' . $this->db->protectIdentifiers($field);
            } else {
                $fieldIdentified = $this->db->protectIdentifiers($field);
            }
            
            switch ($operator) {
                case 'BETWEEN':
                    if (is_array($value) && count($value) == 2) {
                        $this->queryParts['where'][] = "{$fieldIdentified} BETWEEN ? AND ?";
                        $this->params[] = $this->formatValue($value[0], $valueType);
                        $this->params[] = $this->formatValue($value[1], $valueType);
                    }
                    break;
                    
                case 'IN':
                case 'NOT IN':
                    if (is_array($value) && !empty($value)) {
                        $placeholders = implode(', ', array_fill(0, count($value), '?'));
                        $this->queryParts['where'][] = "{$fieldIdentified} {$operator} ({$placeholders})";
                        foreach ($value as $val) {
                            $this->params[] = $this->formatValue($val, $valueType);
                        }
                    }
                    break;
                    
                case 'LIKE':
                case 'NOT LIKE':
                    $this->queryParts['where'][] = "{$fieldIdentified} {$operator} ?";
                    $this->params[] = $this->formatValue($value, $valueType);
                    break;
                    
                case 'IS NULL':
                case 'IS NOT NULL':
                    $this->queryParts['where'][] = "{$fieldIdentified} {$operator}";
                    break;
                    
                default:
                    $this->queryParts['where'][] = "{$fieldIdentified} {$operator} ?";
                    $this->params[] = $this->formatValue($value, $valueType);
            }
        }
    }

    /**
     * Add subqueries
     */
    protected function addSubqueries(array $subqueries): void
    {
        foreach ($subqueries as $subquery) {
            if (empty($subquery['alias']) || empty($subquery['query'])) {
                continue;
            }
            
            $alias = $subquery['alias'];
            $query = $subquery['query'];
            $type = $subquery['type'] ?? 'select';
            
            if ($type === 'select') {
                $this->queryParts['select'][] = "({$query}) AS " . $this->db->escapeIdentifiers($alias);
            } elseif ($type === 'join') {
                $this->queryParts['joins'][] = "LEFT JOIN ({$query}) AS {$alias} ON {$subquery['condition']}";
            }
        }
    }

    /**
     * Format value based on type
     */
    protected function formatValue($value, string $type = null)
    {
        if ($value === null) {
            return null;
        }
        
        if ($type) {
            switch ($type) {
                case 'int':
                case 'integer':
                    return (int) $value;
                case 'decimal':
                case 'float':
                    return (float) $value;
                case 'date':
                    return date('Y-m-d', strtotime($value));
                case 'datetime':
                    return date('Y-m-d H:i:s', strtotime($value));
                case 'boolean':
                    return (bool) $value ? 1 : 0;
                default:
                    return (string) $value;
            }
        }
        
        return $value;
    }

    /**
     * Add grouping
     */
    protected function addGrouping(array $grouping): void
    {
        foreach ($grouping as $group) {
            if (!empty($group['field'])) {
                $table = $group['table'] ?? null;
                
                if ($table) {
                    $field = $this->db->protectIdentifiers($table) . '.' . $this->db->protectIdentifiers($group['field']);
                } else {
                    $field = $this->db->protectIdentifiers($group['field']);
                }
                
                $this->queryParts['group_by'][] = $field;
            }
        }
    }

    /**
     * Add sorting
     */
    protected function addSorting(array $sorting): void
    {
        foreach ($sorting as $sort) {
            if (!empty($sort['field'])) {
                $table = $sort['table'] ?? null;
                
                if ($table) {
                    $field = $this->db->protectIdentifiers($table) . '.' . $this->db->protectIdentifiers($sort['field']);
                } else {
                    $field = $this->db->protectIdentifiers($sort['field']);
                }
                
                $direction = strtoupper($sort['direction'] ?? 'ASC');
                $direction = in_array($direction, ['ASC', 'DESC']) ? $direction : 'ASC';
                
                $this->queryParts['order_by'][] = "{$field} {$direction}";
            }
        }
    }

    /**
     * Compile final SQL query
     */
    protected function compileQuery(): string
    {
        $sql = "SELECT " . implode(', ', $this->queryParts['select']) . "\n";
        
        if (!empty($this->queryParts['from'])) {
            $sql .= "FROM " . implode(', ', $this->queryParts['from']) . "\n";
        }
        
        if (!empty($this->queryParts['joins'])) {
            $sql .= implode("\n", $this->queryParts['joins']) . "\n";
        }
        
        if (!empty($this->queryParts['where'])) {
            $sql .= "WHERE " . implode(' AND ', $this->queryParts['where']) . "\n";
        }
        
        if (!empty($this->queryParts['group_by'])) {
            $sql .= "GROUP BY " . implode(', ', $this->queryParts['group_by']) . "\n";
        }
        
        if (!empty($this->queryParts['having'])) {
            $sql .= "HAVING " . implode(' AND ', $this->queryParts['having']) . "\n";
        }
        
        if (!empty($this->queryParts['order_by'])) {
            $sql .= "ORDER BY " . implode(', ', $this->queryParts['order_by']) . "\n";
        }
        
        if ($this->queryParts['limit'] !== null) {
            $sql .= "LIMIT " . (int) $this->queryParts['limit'] . "\n";
        }
        
        if ($this->queryParts['offset'] !== null) {
            $sql .= "OFFSET " . (int) $this->queryParts['offset'] . "\n";
        }
        
        return $sql;
    }

    /**
     * Get query parameters
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Execute query with pagination
     */
    public function executeWithPagination(array $config, int $page = 1, int $perPage = null): array
    {
        $perPage = $perPage ?? 50;
        $offset = ($page - 1) * $perPage;
        
        // Build query with pagination
        $config['limit'] = $perPage;
        $config['offset'] = $offset;
        $sql = $this->buildQuery($config);
        
        // Execute query
        $startTime = microtime(true);
        $result = $this->db->query($sql, $this->params)->getResultArray();
        $executionTime = microtime(true) - $startTime;
        
        // Get total count (without pagination)
        unset($config['limit'], $config['offset']);
        $countSql = $this->buildCountQuery($config);
        $countResult = $this->db->query($countSql, $this->params)->getRowArray();
        $total = (int) ($countResult['total_count'] ?? 0);
        
        return [
            'data' => $result,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => ceil($total / $perPage),
            'execution_time' => $executionTime,
            'sql' => $sql
        ];
    }

    /**
     * Build count query
     */
    protected function buildCountQuery(array $config): string
    {
        // Build count query
        $countConfig = $config;
        
        if ($config['report_type'] === 'custom_sql') {
            // For custom SQL, wrap it in a count query
            $sql = $this->buildQuery($config);
            return "SELECT COUNT(*) as total_count FROM ({$sql}) as count_subquery";
        }
        
        $countConfig['columns'] = [['field' => 'COUNT(*)', 'alias' => 'total_count']];
        unset($countConfig['limit'], $countConfig['offset'], $countConfig['sorting']);
        
        return $this->buildQuery($countConfig);
    }

    /**
     * Test SQL query
     */
    public function testQuery(string $sql, array $params = []): array
    {
        try {
            $startTime = microtime(true);
            $result = $this->db->query($sql, $params)->getResultArray();
            $executionTime = microtime(true) - $startTime;
            
            return [
                'success' => true,
                'data' => $result,
                'count' => count($result),
                'execution_time' => $executionTime
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'sql_error' => $this->db->error()
            ];
        }
    }
}