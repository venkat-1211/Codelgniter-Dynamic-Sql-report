<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportModel extends Model
{
    protected $table = 'reports';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'report_name', 'description', 'base_query', 'selected_columns',
        'where_conditions', 'group_by', 'order_by', 'filter_parameters',
        'is_active', 'created_by', 'updated_by'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation rules
    protected $validationRules = [
        'report_name' => 'required|min_length[3]|max_length[255]',
        'base_query' => 'required',
    ];

    protected $validationMessages = [
        'report_name' => [
            'required' => 'Report name is required',
            'min_length' => 'Report name must be at least 3 characters',
            'max_length' => 'Report name cannot exceed 255 characters',
        ],
        'base_query' => [
            'required' => 'SQL query is required',
        ],
    ];

    /**
     * Parse SQL query and extract components
     */
    public function parseQuery($sql)
    {
        $components = [
            'selected_columns' => [],
            'where_conditions' => [],
            'group_by' => [],
            'order_by' => [],
            'filter_parameters' => []
        ];

        try {
            // Normalize SQL for easier parsing
            $sql = trim($sql);
            $sql = rtrim($sql, ';');
            
            // Remove comments
            $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
            $sql = preg_replace('/--.*?$/m', '', $sql);
            
            // Extract SELECT columns
            if (preg_match('/SELECT\s+(.*?)\s+FROM\s+/is', $sql, $matches)) {
                $selectPart = $matches[1];
                $columns = $this->splitSelectColumns($selectPart);
                foreach ($columns as $col) {
                    $col = trim($col);
                    if (!empty($col)) {
                        // Extract alias
                        $alias = $this->extractColumnAlias($col);
                        $original = $this->cleanColumnOriginal($col);
                        
                        $components['selected_columns'][] = [
                            'original' => $original,
                            'alias' => $alias,
                            'display' => true
                        ];
                    }
                }
            }

            // Extract all WHERE conditions including subqueries
            $components['where_conditions'] = $this->extractAllWhereConditions($sql);
            
            // Extract filter parameters from WHERE conditions
            $components['filter_parameters'] = $this->extractAllFilterParametersFromConditions($components['where_conditions']);

            // Extract GROUP BY (including complex GROUP BY clauses)
            $components['group_by'] = $this->extractGroupBy($sql);

            // Extract ORDER BY (including complex ORDER BY clauses)
            $components['order_by'] = $this->extractOrderBy($sql);

        } catch (\Exception $e) {
            log_message('error', 'Query parsing error: ' . $e->getMessage());
        }

        return $components;
    }

    /**
     * Extract all WHERE conditions from query including subqueries
     */
    private function extractAllWhereConditions($sql)
    {
        $allConditions = [];
        
        // Find all WHERE clauses (including in subqueries)
        $pattern = '/WHERE\s+((?:(?:(?![HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT]).)*))/is';
        
        // First, let's find all WHERE positions
        $wherePositions = [];
        $offset = 0;
        
        while (($pos = stripos($sql, 'WHERE', $offset)) !== false) {
            $wherePositions[] = $pos;
            $offset = $pos + 5;
        }
        
        // Process each WHERE clause
        foreach ($wherePositions as $pos) {
            // Extract the WHERE clause
            $subSql = substr($sql, $pos);
            
            // Find the end of the WHERE clause (next GROUP BY, ORDER BY, LIMIT, or end)
            $endPattern = '/(?:\s+(?:GROUP\s+BY|ORDER\s+BY|LIMIT|HAVING)|\s*$)/i';
            if (preg_match($endPattern, $subSql, $endMatch, PREG_OFFSET_CAPTURE)) {
                $endPos = $endMatch[0][1];
                $whereClause = substr($subSql, 5, $endPos - 5); // 5 = length of "WHERE"
                $whereClause = trim($whereClause);
                
                if (!empty($whereClause)) {
                    // Extract conditions from this WHERE clause
                    $conditions = $this->parseWhereClause($whereClause);
                    $allConditions = array_merge($allConditions, $conditions);
                }
            }
        }
        
        // Also check for HAVING clauses
        $havingPositions = [];
        $offset = 0;
        
        while (($pos = stripos($sql, 'HAVING', $offset)) !== false) {
            $havingPositions[] = $pos;
            $offset = $pos + 6;
        }
        
        // Process each HAVING clause
        foreach ($havingPositions as $pos) {
            $subSql = substr($sql, $pos);
            
            // Find the end of the HAVING clause
            $endPattern = '/(?:\s+(?:GROUP\s+BY|ORDER\s+BY|LIMIT)|\s*$)/i';
            if (preg_match($endPattern, $subSql, $endMatch, PREG_OFFSET_CAPTURE)) {
                $endPos = $endMatch[0][1];
                $havingClause = substr($subSql, 6, $endPos - 6); // 6 = length of "HAVING"
                $havingClause = trim($havingClause);
                
                if (!empty($havingClause)) {
                    $conditions = $this->parseWhereClause($havingClause);
                    foreach ($conditions as &$cond) {
                        $cond['is_having'] = true;
                    }
                    $allConditions = array_merge($allConditions, $conditions);
                }
            }
        }
        
        return $allConditions;
    }

    /**
     * Parse WHERE clause into individual conditions
     */
    private function parseWhereClause($whereClause)
    {
        $conditions = [];
        
        // Remove subqueries temporarily for easier parsing
        $placeholderMap = [];
        $counter = 0;
        
        // Replace subqueries with placeholders
        $whereClause = preg_replace_callback(
            '/\(SELECT\s+.*?\)/is',
            function($matches) use (&$placeholderMap, &$counter) {
                $placeholder = "__SUBQUERY_{$counter}__";
                $placeholderMap[$placeholder] = $matches[0];
                $counter++;
                return $placeholder;
            },
            $whereClause
        );
        
        // Also replace simple parentheses for complex expressions
        $whereClause = preg_replace_callback(
            '/\((?!__SUBQUERY_)[^)]+\)/',
            function($matches) use (&$placeholderMap, &$counter) {
                $placeholder = "__PAREN_{$counter}__";
                $placeholderMap[$placeholder] = $matches[0];
                $counter++;
                return $placeholder;
            },
            $whereClause
        );
        
        // Split by AND/OR at top level (not inside placeholders)
        $tokens = $this->splitByLogicalOperators($whereClause);
        
        $currentOperator = 'AND';
        foreach ($tokens as $token) {
            if (strtoupper($token) === 'AND' || strtoupper($token) === 'OR') {
                $currentOperator = strtoupper($token);
                continue;
            }
            
            // Restore placeholders in the token
            $originalToken = $token;
            foreach ($placeholderMap as $placeholder => $original) {
                if (strpos($originalToken, $placeholder) !== false) {
                    $originalToken = str_replace($placeholder, $original, $originalToken);
                }
            }
            
            $parsedCondition = $this->parseSingleCondition($originalToken);
            if ($parsedCondition) {
                $parsedCondition['operator'] = $currentOperator;
                $conditions[] = $parsedCondition;
            }
            
            // Reset operator to AND for next condition
            $currentOperator = 'AND';
        }
        
        return $conditions;
    }

    /**
     * Split string by logical operators at top level
     */
    private function splitByLogicalOperators($str)
    {
        $tokens = [];
        $current = '';
        $depth = 0;
        $inString = false;
        $stringChar = '';
        $i = 0;
        $len = strlen($str);
        
        while ($i < $len) {
            $char = $str[$i];
            
            // Handle strings
            if (($char === "'" || $char === '"') && ($i === 0 || $str[$i-1] !== '\\')) {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($stringChar === $char) {
                    $inString = false;
                    $stringChar = '';
                }
                $current .= $char;
                $i++;
                continue;
            }
            
            // Handle parentheses
            if ($char === '(' && !$inString) {
                $depth++;
                $current .= $char;
                $i++;
                continue;
            }
            
            if ($char === ')' && !$inString) {
                $depth--;
                $current .= $char;
                $i++;
                continue;
            }
            
            // Check for AND/OR operators at depth 0
            if ($depth === 0 && !$inString) {
                // Check for AND
                if (strtoupper(substr($str, $i, 4)) === 'AND ') {
                    if (trim($current) !== '') {
                        $tokens[] = trim($current);
                    }
                    $tokens[] = 'AND';
                    $current = '';
                    $i += 4;
                    continue;
                }
                
                // Check for OR
                if (strtoupper(substr($str, $i, 3)) === 'OR ') {
                    if (trim($current) !== '') {
                        $tokens[] = trim($current);
                    }
                    $tokens[] = 'OR';
                    $current = '';
                    $i += 3;
                    continue;
                }
            }
            
            $current .= $char;
            $i++;
        }
        
        if (trim($current) !== '') {
            $tokens[] = trim($current);
        }
        
        return $tokens;
    }

    /**
     * Parse a single condition
     */
    private function parseSingleCondition($condition)
    {
        $condition = trim($condition);
        
        // Pattern 1: EXISTS / NOT EXISTS subquery
        if (preg_match('/^(NOT\s+)?EXISTS\s*\(.*\)$/i', $condition, $matches)) {
            return [
                'condition' => $condition,
                'type' => 'EXISTS',
                'column' => isset($matches[1]) ? 'NOT EXISTS' : 'EXISTS',
                'editable' => false,
                'subquery' => true
            ];
        }
        
        // Pattern 2: column IN (values)
        if (preg_match('/^([\w\.]+(?:\([^)]+\))?)\s+IN\s*\(\s*(.+?)\s*\)$/i', $condition, $matches)) {
            $column = trim($matches[1]);
            $values = trim($matches[2]);
            
            $valueList = $this->splitSqlByComma($values);
            $cleanValues = array_map(function($v) {
                $v = trim($v);
                return trim($v, " '\"");
            }, $valueList);
            
            return [
                'condition' => $condition,
                'type' => 'IN',
                'column' => $column,
                'values' => array_filter($cleanValues),
                'editable' => true
            ];
        }
        
        // Pattern 3: function IN (values) like LOWER(column) IN (...)
        if (preg_match('/^([\w]+\s*\([^)]+\))\s+IN\s*\(\s*(.+?)\s*\)$/i', $condition, $matches)) {
            $column = trim($matches[1]);
            $values = trim($matches[2]);
            
            $valueList = $this->splitSqlByComma($values);
            $cleanValues = array_map(function($v) {
                $v = trim($v);
                return trim($v, " '\"");
            }, $valueList);
            
            return [
                'condition' => $condition,
                'type' => 'FUNCTION_IN',
                'column' => $column,
                'values' => array_filter($cleanValues),
                'editable' => true
            ];
        }
        
        // Pattern 4: column BETWEEN x AND y
        if (preg_match('/^([\w\.]+)\s+BETWEEN\s+(.+?)\s+AND\s+(.+)$/i', $condition, $matches)) {
            $column = trim($matches[1]);
            $from = trim($matches[2]);
            $to = trim($matches[3]);
            
            $from = trim($from, " '\"");
            $to = trim($to, " '\"");
            
            return [
                'condition' => $condition,
                'type' => 'BETWEEN',
                'column' => $column,
                'from' => $from,
                'to' => $to,
                'editable' => true
            ];
        }
        
        // Pattern 5: function BETWEEN x AND y like DATE(column) BETWEEN ...
        if (preg_match('/^([\w]+\s*\([^)]+\))\s+BETWEEN\s+(.+?)\s+AND\s+(.+)$/i', $condition, $matches)) {
            $column = trim($matches[1]);
            $from = trim($matches[2]);
            $to = trim($matches[3]);
            
            $from = trim($from, " '\"");
            $to = trim($to, " '\"");
            
            return [
                'condition' => $condition,
                'type' => 'FUNCTION_BETWEEN',
                'column' => $column,
                'from' => $from,
                'to' => $to,
                'editable' => true
            ];
        }
        
        // Pattern 6: column LIKE pattern
        if (preg_match('/^([\w\.]+)\s+LIKE\s+(.+)$/i', $condition, $matches)) {
            $column = trim($matches[1]);
            $pattern = trim($matches[2]);
            $value = trim($pattern, " '\"");
            
            // Extract value without wildcards for default
            $defaultValue = str_replace(['%', '_'], '', $value);
            
            return [
                'condition' => $condition,
                'type' => 'LIKE',
                'column' => $column,
                'pattern' => $pattern,
                'value' => $defaultValue,
                'editable' => true
            ];
        }
        
        // Pattern 7: function LIKE pattern like LOWER(column) LIKE ...
        if (preg_match('/^([\w]+\s*\([^)]+\))\s+LIKE\s+(.+)$/i', $condition, $matches)) {
            $column = trim($matches[1]);
            $pattern = trim($matches[2]);
            $value = trim($pattern, " '\"");
            
            $defaultValue = str_replace(['%', '_'], '', $value);
            
            return [
                'condition' => $condition,
                'type' => 'FUNCTION_LIKE',
                'column' => $column,
                'pattern' => $pattern,
                'value' => $defaultValue,
                'editable' => true
            ];
        }
        
        // Pattern 8: FIND_IN_SET function
        if (preg_match('/^FIND_IN_SET\(([^,]+),\s*([^)]+)\)\s*(>|>=|<|<=|=|!=)\s*(\d+)$/i', $condition, $matches)) {
            return [
                'condition' => $condition,
                'type' => 'FIND_IN_SET',
                'column' => 'FIND_IN_SET(' . trim($matches[1]) . ', ' . trim($matches[2]) . ')',
                'operator' => trim($matches[3]),
                'value' => trim($matches[4]),
                'editable' => true
            ];
        }
        
        // Pattern 9: column comparison (with quoted values)
        if (preg_match('/^([\w\.]+)\s*([=!<>]+|!=|<=|>=|<>)\s*(.+)$/i', $condition, $matches)) {
            $column = trim($matches[1]);
            $operator = trim($matches[2]);
            $value = trim($matches[3]);
            
            // Store the original value WITH quotes
            $originalValue = $value;
            
            // Extract clean value without quotes for display
            $cleanValue = $value;
            if (strlen($value) >= 2) {
                if (($value[0] === "'" && substr($value, -1) === "'") ||
                    ($value[0] === '"' && substr($value, -1) === '"')) {
                    $cleanValue = substr($value, 1, -1);
                }
            }
            
            return [
                'condition' => $condition,
                'type' => 'COMPARISON',
                'column' => $column,
                'operator' => $operator,
                'value' => $cleanValue,
                'original_value' => $originalValue, // Store with quotes
                'editable' => true
            ];
        }
        
        // Pattern 10: function comparison like LOWER(column) = 'value'
        if (preg_match('/^([\w]+\s*\([^)]+\))\s*([=!<>]+|!=|<=|>=|<>)\s*(.+)$/i', $condition, $matches)) {
            $column = trim($matches[1]);
            $operator = trim($matches[2]);
            $value = trim($matches[3]);
            
            if (preg_match('/^[\'"](.*)[\'"]$/', $value, $quoteMatches)) {
                $cleanValue = $quoteMatches[1];
            } else {
                $cleanValue = $value;
            }
            
            return [
                'condition' => $condition,
                'type' => 'FUNCTION_COMPARISON',
                'column' => $column,
                'operator' => $operator,
                'value' => $cleanValue,
                'editable' => true
            ];
        }
        
        // Pattern 11: IS NULL / IS NOT NULL
        if (preg_match('/^([\w\.]+)\s+(IS\s+(?:NOT\s+)?NULL)$/i', $condition, $matches)) {
            $column = trim($matches[1]);
            $operator = trim($matches[2]);
            
            return [
                'condition' => $condition,
                'type' => 'IS_NULL',
                'column' => $column,
                'operator' => $operator,
                'editable' => true
            ];
        }
        
        // Pattern 12: function IS NULL like LOWER(column) IS NULL
        if (preg_match('/^([\w]+\s*\([^)]+\))\s+(IS\s+(?:NOT\s+)?NULL)$/i', $condition, $matches)) {
            $column = trim($matches[1]);
            $operator = trim($matches[2]);
            
            return [
                'condition' => $condition,
                'type' => 'FUNCTION_IS_NULL',
                'column' => $column,
                'operator' => $operator,
                'editable' => true
            ];
        }
        
        // Default: complex condition
        return [
            'condition' => $condition,
            'type' => 'COMPLEX',
            'column' => $this->extractColumnFromCondition($condition),
            'editable' => false
        ];
    }

    /**
     * Extract GROUP BY clause
     */
    private function extractGroupBy($sql)
    {
        $groupByItems = [];
        
        // Find GROUP BY position
        $groupByPos = stripos($sql, 'GROUP BY');
        if ($groupByPos === false) {
            return $groupByItems;
        }
        
        // Extract from GROUP BY to next clause or end
        $subSql = substr($sql, $groupByPos + 8); // 8 = length of "GROUP BY"
        
        // Find the end (ORDER BY, LIMIT, HAVING, or end of string)
        $endPattern = '/(?:\s+(?:ORDER\s+BY|LIMIT|HAVING)|\s*$)/i';
        if (preg_match($endPattern, $subSql, $matches, PREG_OFFSET_CAPTURE)) {
            $endPos = $matches[0][1];
            $groupByClause = substr($subSql, 0, $endPos);
            $groupByClause = trim($groupByClause);
            
            // Split GROUP BY items
            $items = $this->splitSqlByComma($groupByClause);
            foreach ($items as $item) {
                $item = trim($item);
                if (!empty($item)) {
                    $groupByItems[] = [
                        'original' => $item,
                        'display' => true
                    ];
                }
            }
        }
        
        return $groupByItems;
    }

    /**
     * Extract ORDER BY clause
     */
    private function extractOrderBy($sql)
    {
        $orderByItems = [];
        
        // Find ORDER BY position
        $orderByPos = stripos($sql, 'ORDER BY');
        if ($orderByPos === false) {
            return $orderByItems;
        }
        
        // Extract from ORDER BY to next clause or end
        $subSql = substr($sql, $orderByPos + 8); // 8 = length of "ORDER BY"
        
        // Find the end (LIMIT or end of string)
        $endPattern = '/(?:\s+LIMIT|\s*$)/i';
        if (preg_match($endPattern, $subSql, $matches, PREG_OFFSET_CAPTURE)) {
            $endPos = $matches[0][1];
            $orderByClause = substr($subSql, 0, $endPos);
            $orderByClause = trim($orderByClause);
            
            // Split ORDER BY items
            $items = $this->splitSqlByComma($orderByClause);
            foreach ($items as $item) {
                $item = trim($item);
                if (!empty($item)) {
                    $parts = preg_split('/\s+/', $item, 2);
                    $column = trim($parts[0]);
                    $direction = isset($parts[1]) ? strtoupper(trim($parts[1])) : 'ASC';
                    
                    $orderByItems[] = [
                        'original' => $column,
                        'direction' => $direction
                    ];
                }
            }
        }
        
        return $orderByItems;
    }

    /**
     * Extract all filter parameters from conditions
     */
    private function extractAllFilterParametersFromConditions($whereConditions)
    {
        $filters = [];
        
        foreach ($whereConditions as $condition) {
            if (!$condition['editable']) {
                continue;
            }
            
            $filter = [
                'type' => $condition['type'],
                'column' => $condition['column'],
                'operator' => isset($condition['operator']) ? $condition['operator'] : $condition['type'],
                'editable' => true
            ];
            
            // Add values based on type
            switch ($condition['type']) {
                case 'IN':
                case 'FUNCTION_IN':
                    $filter['values'] = isset($condition['values']) ? $condition['values'] : [];
                    $filter['multiple'] = true;
                    break;
                    
                case 'BETWEEN':
                case 'FUNCTION_BETWEEN':
                    $filter['from'] = isset($condition['from']) ? $condition['from'] : '';
                    $filter['to'] = isset($condition['to']) ? $condition['to'] : '';
                    break;
                    
                case 'LIKE':
                case 'FUNCTION_LIKE':
                    $filter['value'] = isset($condition['value']) ? $condition['value'] : '';
                    $filter['wildcard'] = true;
                    break;
                    
                case 'COMPARISON':
                case 'FUNCTION_COMPARISON':
                case 'FIND_IN_SET':
                    $filter['value'] = isset($condition['value']) ? $condition['value'] : '';
                    $filter['operator'] = isset($condition['operator']) ? $condition['operator'] : '=';
                    break;
                    
                case 'IS_NULL':
                case 'FUNCTION_IS_NULL':
                    $filter['value'] = isset($condition['operator']) ? $condition['operator'] : 'IS NULL';
                    break;
            }
            
            if (isset($condition['is_having'])) {
                $filter['is_having'] = true;
            }
            
            $filters[] = $filter;
        }
        
        return $filters;
    }

    // Helper methods (keep your existing implementations but add these improvements):

    private function splitSelectColumns($selectPart)
    {
        $columns = [];
        $current = '';
        $depth = 0;
        $inString = false;
        $stringChar = '';
        $length = strlen($selectPart);
        
        for ($i = 0; $i < $length; $i++) {
            $char = $selectPart[$i];
            
            if (($char === "'" || $char === '"') && ($i === 0 || $selectPart[$i-1] !== '\\')) {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($stringChar === $char) {
                    $inString = false;
                    $stringChar = '';
                }
                $current .= $char;
            } elseif ($char === '(' && !$inString) {
                $depth++;
                $current .= $char;
            } elseif ($char === ')' && !$inString) {
                $depth--;
                $current .= $char;
            } elseif ($char === ',' && $depth === 0 && !$inString) {
                $columns[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }
        
        if (trim($current) !== '') {
            $columns[] = trim($current);
        }
        
        return $columns;
    }

    private function extractColumnAlias($column)
    {
        $column = trim($column);
        
        // Check for AS alias
        if (preg_match('/(.*?)\s+(?:AS\s+)?([\w]+)$/i', $column, $matches)) {
            $potentialAlias = trim($matches[2]);
            $beforeAlias = trim($matches[1]);
            
            if (preg_match('/\)\s*$/', $beforeAlias) || 
                preg_match('/^[\w\.]+$/', $potentialAlias) ||
                preg_match('/\w+\(/i', $beforeAlias)) {
                return $potentialAlias;
            }
        }
        
        // If no alias found, use the column name
        $parts = explode('.', $column);
        $lastPart = end($parts);
        $lastPart = preg_replace('/[^a-zA-Z0-9_]/', '', $lastPart);
        
        return $lastPart ?: 'column_' . uniqid();
    }

    private function cleanColumnOriginal($column)
    {
        $column = preg_replace('/\s+AS\s+[\w]+$/i', '', $column);
        $column = preg_replace('/\s+as\s+[\w]+$/i', '', $column);
        return trim($column);
    }

    private function splitSqlByComma($sql)
    {
        $result = [];
        $current = '';
        $depth = 0;
        $inString = false;
        $stringChar = '';
        $length = strlen($sql);
        
        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            
            if (($char === "'" || $char === '"') && ($i === 0 || $sql[$i-1] !== '\\')) {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($stringChar === $char) {
                    $inString = false;
                    $stringChar = '';
                }
                $current .= $char;
            } elseif ($char === '(' && !$inString) {
                $depth++;
                $current .= $char;
            } elseif ($char === ')' && !$inString) {
                $depth--;
                $current .= $char;
            } elseif ($char === ',' && $depth === 0 && !$inString) {
                $result[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }
        
        if (trim($current) !== '') {
            $result[] = trim($current);
        }
        
        return $result;
    }

    private function extractColumnFromCondition($condition)
    {
        // Try to extract column from common patterns
        if (preg_match('/^([\w\.]+)/', $condition, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/^([\w]+\([^)]+\))/', $condition, $matches)) {
            return $matches[1];
        }
        
        // For complex conditions, try to find any column-like pattern
        if (preg_match('/([\w]+\.[\w]+)/', $condition, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/([\w]+\([^)]+\))/', $condition, $matches)) {
            return $matches[1];
        }
        
        return 'Complex Condition';
    }

    /**
     * Execute report query with dynamic filters
     */
    public function executeReport($reportId, $filters = [])
    {
        $report = $this->find($reportId);
        if (!$report) {
            throw new \Exception('Report not found');
        }

        $sql = $report['base_query'];
        $db = \Config\Database::connect();
        
        // Apply dynamic filters if provided
        if (!empty($filters)) {
            $sql = $this->applyFilters($sql, $filters, $db);
        }

        // Execute query
        return $db->query($sql)->getResultArray();
    }

    /**
     * Apply dynamic filters to SQL
     */
    // private function applyFilters($sql, $filters, $db)
    // {
    //     if (empty($filters)) {
    //         return $sql;
    //     }
        
    //     // Get the report to access original conditions
    //     $report = $this->find($filters['report_id'] ?? 0);
    //     $originalConditions = [];
    //     if ($report && isset($report['where_conditions'])) {
    //         $originalConditions = json_decode($report['where_conditions'], true);
    //     }
        
    //     $replacements = [];
        
    //     foreach ($filters as $key => $value) {
    //         if (empty($value) && $value !== '0' && $value !== 0) {
    //             continue;
    //         }
            
    //         // Skip special keys
    //         if (in_array($key, ['group_by', 'order_by', 'report_id'])) {
    //             continue;
    //         }
            
    //         // Find matching original condition
    //         $originalCondition = null;
    //         foreach ($originalConditions as $cond) {
    //             if (isset($cond['column']) && $cond['column'] === $key) {
    //                 $originalCondition = $cond;
    //                 break;
    //             }
    //         }
            
    //         if (!$originalCondition) {
    //             continue;
    //         }
            
    //         // Build replacement based on condition type
    //         switch ($originalCondition['type'] ?? '') {
    //             case 'IN':
    //             case 'FUNCTION_IN':
    //                 if (is_array($value) && !empty($value)) {
    //                     $escapedValues = array_map(function($v) use ($db) {
    //                         return "'" . $db->escape($v) . "'";
    //                     }, $value);
    //                     $replacements[$originalCondition['condition']] = 
    //                         $originalCondition['column'] . " IN (" . implode(',', $escapedValues) . ")";
    //                 }
    //                 break;
                    
    //             case 'BETWEEN':
    //             case 'FUNCTION_BETWEEN':
    //                 $fromKey = 'FROM_' . $key;
    //                 $toKey = 'TO_' . $key;
                    
    //                 if (isset($filters[$fromKey]) && isset($filters[$toKey]) && 
    //                     !empty($filters[$fromKey]) && !empty($filters[$toKey])) {
    //                     $replacements[$originalCondition['condition']] = 
    //                         $originalCondition['column'] . " BETWEEN '" . $db->escape($filters[$fromKey]) . "' AND '" . $db->escape($filters[$toKey]) . "'";
    //                 }
    //                 break;
                    
    //             case 'LIKE':
    //             case 'FUNCTION_LIKE':
    //                 $likeKey = 'LIKE_' . $key;
    //                 if (isset($filters[$likeKey]) && !empty($filters[$likeKey])) {
    //                     $replacements[$originalCondition['condition']] = 
    //                         $originalCondition['column'] . " LIKE '%" . $db->escapeLikeString($filters[$likeKey]) . "%'";
    //                 }
    //                 break;
                    
    //             case 'COMPARISON':
    //             case 'FUNCTION_COMPARISON':
    //             case 'FIND_IN_SET':
    //                 $opKey = 'OP_' . $key;
    //                 $operator = isset($filters[$opKey]) ? $filters[$opKey] : ($originalCondition['operator'] ?? '=');
                    
    //                 if ($value === 'NULL') {
    //                     $replacements[$originalCondition['condition']] = $originalCondition['column'] . " IS NULL";
    //                 } elseif ($value === 'NOT NULL') {
    //                     $replacements[$originalCondition['condition']] = $originalCondition['column'] . " IS NOT NULL";
    //                 } else {
    //                     $replacements[$originalCondition['condition']] = 
    //                         $originalCondition['column'] . " " . $operator . " '" . $db->escape($value) . "'";
    //                 }
    //                 break;
                    
    //             case 'IS_NULL':
    //             case 'FUNCTION_IS_NULL':
    //                 if ($value === 'IS NULL' || $value === 'IS NOT NULL') {
    //                     $replacements[$originalCondition['condition']] = $originalCondition['column'] . " " . $value;
    //                 }
    //                 break;
    //         }
    //     }
        
    //     // Apply replacements to SQL
    //     foreach ($replacements as $original => $replacement) {
    //         $sql = str_replace($original, $replacement, $sql);
    //     }
        
    //     // Apply GROUP BY
    //     if (isset($filters['group_by']) && !empty($filters['group_by'])) {
    //         $groupByColumns = array_filter((array)$filters['group_by']);
    //         if (!empty($groupByColumns)) {
    //             $groupByString = 'GROUP BY ' . implode(', ', $groupByColumns);
                
    //             if (stripos($sql, 'GROUP BY') !== false) {
    //                 $sql = preg_replace('/GROUP BY\s+(.*?)(?:\s+(?:ORDER BY|LIMIT)|\s*$)/is', 
    //                                   $groupByString . ' ', 
    //                                   $sql);
    //             } else {
    //                 if (stripos($sql, 'ORDER BY') !== false) {
    //                     $sql = preg_replace('/(\s+ORDER BY)/i', ' ' . $groupByString . '$1', $sql);
    //                 } else {
    //                     $sql .= ' ' . $groupByString;
    //                 }
    //             }
    //         }
    //     }
        
    //     // Apply ORDER BY
    //     if (isset($filters['order_by']) && !empty($filters['order_by'])) {
    //         $orderByClauses = [];
    //         foreach ((array)$filters['order_by'] as $order) {
    //             if (!empty($order['column'])) {
    //                 $direction = isset($order['direction']) ? strtoupper($order['direction']) : 'ASC';
    //                 $orderByClauses[] = $order['column'] . ' ' . $direction;
    //             }
    //         }
            
    //         if (!empty($orderByClauses)) {
    //             $orderByString = 'ORDER BY ' . implode(', ', $orderByClauses);
                
    //             if (stripos($sql, 'ORDER BY') !== false) {
    //                 $sql = preg_replace('/ORDER BY\s+(.*?)(?:\s+LIMIT|\s*$)/is', 
    //                                   $orderByString . ' ', 
    //                                   $sql);
    //             } else {
    //                 $sql .= ' ' . $orderByString;
    //             }
    //         }
    //     }
        
    //     return $sql;
    // }
    // private function applyFilters($sql, $filters, $db)
    // {
    //     if (empty($filters)) {
    //         return $sql;
    //     }
        
    //     // Get the report to access original conditions
    //     $report = $this->find($filters['report_id'] ?? 0);
    //     $originalConditions = [];
    //     if ($report && isset($report['where_conditions'])) {
    //         $originalConditions = json_decode($report['where_conditions'], true);
    //     }
        
    //     // Store modifications to apply
    //     $modifications = [];
        
    //     foreach ($filters as $key => $value) {
    //         if (empty($value) && $value !== '0' && $value !== 0) {
    //             continue;
    //         }
            
    //         // Skip special keys
    //         if (in_array($key, ['group_by', 'order_by', 'report_id'])) {
    //             continue;
    //         }
            
    //         // Skip operator keys (they're handled with their main key)
    //         if (strpos($key, 'OP_') === 0) {
    //             continue;
    //         }
            
    //         // Skip FROM/TO keys (handled with BETWEEN)
    //         if (strpos($key, 'FROM_') === 0 || strpos($key, 'TO_') === 0) {
    //             continue;
    //         }
            
    //         // Skip LIKE keys (handled with LIKE)
    //         if (strpos($key, 'LIKE_') === 0) {
    //             continue;
    //         }
            
    //         // Find matching original condition
    //         $originalCondition = null;
    //         foreach ($originalConditions as $cond) {
    //             if (isset($cond['column']) && $cond['column'] === $key) {
    //                 $originalCondition = $cond;
    //                 break;
    //             }
    //         }
            
    //         if (!$originalCondition) {
    //             continue;
    //         }
            
    //         // Build new condition based on type
    //         switch ($originalCondition['type'] ?? '') {
    //             case 'IN':
    //             case 'FUNCTION_IN':
    //                 if (is_array($value) && !empty($value)) {
    //                     $escapedValues = array_map(function($v) use ($db) {
    //                         return "'" . $db->escapeString($v) . "'";
    //                     }, $value);
    //                     $newCondition = $originalCondition['column'] . " IN (" . implode(',', $escapedValues) . ")";
    //                     $modifications[] = [
    //                         'original' => $originalCondition['condition'],
    //                         'replacement' => $newCondition
    //                     ];
    //                 }
    //                 break;
                    
    //             case 'BETWEEN':
    //             case 'FUNCTION_BETWEEN':
    //                 $fromKey = 'FROM_' . $key;
    //                 $toKey = 'TO_' . $key;
                    
    //                 $fromValue = isset($filters[$fromKey]) ? $filters[$fromKey] : '';
    //                 $toValue = isset($filters[$toKey]) ? $filters[$toKey] : '';
                    
    //                 if (!empty($fromValue) && !empty($toValue)) {
    //                     $newCondition = $originalCondition['column'] . " BETWEEN '" . $db->escapeString($fromValue) . "' AND '" . $db->escapeString($toValue) . "'";
    //                     $modifications[] = [
    //                         'original' => $originalCondition['condition'],
    //                         'replacement' => $newCondition
    //                     ];
    //                 }
    //                 break;
                    
    //             case 'LIKE':
    //             case 'FUNCTION_LIKE':
    //                 $likeKey = 'LIKE_' . $key;
    //                 $likeValue = isset($filters[$likeKey]) ? $filters[$likeKey] : $value;
                    
    //                 if (!empty($likeValue)) {
    //                     $newCondition = $originalCondition['column'] . " LIKE '%" . $db->escapeLikeString($likeValue) . "%'";
    //                     $modifications[] = [
    //                         'original' => $originalCondition['condition'],
    //                         'replacement' => $newCondition
    //                     ];
    //                 }
    //                 break;
                    
    //             case 'COMPARISON':
    //             case 'FUNCTION_COMPARISON':
    //             case 'FIND_IN_SET':
    //                 // Get operator from OP_key or use default
    //                 $opKey = 'OP_' . $key;
    //                 $operator = isset($filters[$opKey]) ? $filters[$opKey] : ($originalCondition['operator'] ?? '=');
                    
    //                 if ($value === 'NULL') {
    //                     $newCondition = $originalCondition['column'] . " IS NULL";
    //                 } elseif ($value === 'NOT NULL') {
    //                     $newCondition = $originalCondition['column'] . " IS NOT NULL";
    //                 } else {
    //                     // Escape the string properly WITHOUT adding quotes
    //                     $escapedValue = $db->escapeString($value);
                        
    //                     // Determine quote style from original condition
    //                     $quoteStyle = "'";
    //                     if (isset($originalCondition['original_value'])) {
    //                         $origVal = $originalCondition['original_value'];
    //                         // Check if original had double quotes
    //                         if (strlen($origVal) >= 2 && 
    //                             $origVal[0] === '"' && 
    //                             substr($origVal, -1) === '"') {
    //                             $quoteStyle = '"';
    //                         }
    //                     }
                        
    //                     $newCondition = $originalCondition['column'] . " " . $operator . " " . $quoteStyle . $escapedValue . $quoteStyle;
    //                 }
                    
    //                 $modifications[] = [
    //                     'original' => $originalCondition['condition'],
    //                     'replacement' => $newCondition
    //                 ];
    //                 break;
                    
    //             case 'IS_NULL':
    //             case 'FUNCTION_IS_NULL':
    //                 if ($value === 'IS NULL' || $value === 'IS NOT NULL') {
    //                     $newCondition = $originalCondition['column'] . " " . $value;
    //                     $modifications[] = [
    //                         'original' => $originalCondition['condition'],
    //                         'replacement' => $newCondition
    //                     ];
    //                 }
    //                 break;
    //         }
    //     }
        
    //     // Apply all modifications to the SQL
    //     if (!empty($modifications)) {
    //         // Extract WHERE clause
    //         if (preg_match('/(WHERE\s+)(.*?)(?:\s+(?:GROUP BY|ORDER BY|LIMIT)|\s*$)/is', $sql, $matches)) {
    //             $whereKeyword = $matches[1];
    //             $originalWhereClause = $matches[2];
    //             $modifiedWhereClause = $originalWhereClause;
                
    //             // Apply each modification
    //             foreach ($modifications as $mod) {
    //                 // Check if the original condition exists in the WHERE clause
    //                 if (strpos($modifiedWhereClause, $mod['original']) !== false) {
    //                     // Simple string replacement
    //                     $modifiedWhereClause = str_replace($mod['original'], $mod['replacement'], $modifiedWhereClause);
    //                 } else {
    //                     // Try to match without exact quotes (handle different quote styles)
    //                     $originalPattern = preg_quote($mod['original'], '/');
    //                     $originalPattern = str_replace(["\\'", '\\"'], ["['\"]", "['\"]"], $originalPattern);
                        
    //                     if (preg_match('/' . $originalPattern . '/', $modifiedWhereClause)) {
    //                         $modifiedWhereClause = preg_replace('/' . $originalPattern . '/', $mod['replacement'], $modifiedWhereClause, 1);
    //                     }
    //                 }
    //             }
                
    //             // Replace the WHERE clause in the SQL
    //             $sql = str_replace(
    //                 $whereKeyword . $originalWhereClause,
    //                 $whereKeyword . $modifiedWhereClause,
    //                 $sql
    //             );
    //         }
    //     }
        
    //     // Apply GROUP BY
    //     if (isset($filters['group_by']) && !empty($filters['group_by'])) {
    //         $groupByColumns = array_filter((array)$filters['group_by']);
    //         if (!empty($groupByColumns)) {
    //             $groupByString = 'GROUP BY ' . implode(', ', $groupByColumns);
                
    //             if (stripos($sql, 'GROUP BY') !== false) {
    //                 $sql = preg_replace('/GROUP BY\s+(.*?)(?:\s+(?:ORDER BY|LIMIT)|\s*$)/is', 
    //                                   $groupByString . ' ', 
    //                                   $sql);
    //             } else {
    //                 if (stripos($sql, 'ORDER BY') !== false) {
    //                     $sql = preg_replace('/(\s+ORDER BY)/i', ' ' . $groupByString . '$1', $sql);
    //                 } else {
    //                     $sql .= ' ' . $groupByString;
    //                 }
    //             }
    //         }
    //     }
        
    //     // Apply ORDER BY
    //     if (isset($filters['order_by']) && !empty($filters['order_by'])) {
    //         $orderByClauses = [];
    //         foreach ((array)$filters['order_by'] as $order) {
    //             if (!empty($order['column'])) {
    //                 $direction = isset($order['direction']) ? strtoupper($order['direction']) : 'ASC';
    //                 $orderByClauses[] = $order['column'] . ' ' . $direction;
    //             }
    //         }
            
    //         if (!empty($orderByClauses)) {
    //             $orderByString = 'ORDER BY ' . implode(', ', $orderByClauses);
                
    //             if (stripos($sql, 'ORDER BY') !== false) {
    //                 $sql = preg_replace('/ORDER BY\s+(.*?)(?:\s+LIMIT|\s*$)/is', 
    //                                   $orderByString . ' ', 
    //                                   $sql);
    //             } else {
    //                 $sql .= ' ' . $orderByString;
    //             }
    //         }
    //     }
        
    //     return $sql;
    // }
    private function applyFilters($sql, $filters, $db)
{
    if (empty($filters)) {
        return $sql;
    }
    
    // Get the report to access original conditions
    $report = $this->find($filters['report_id'] ?? 0);
    $originalConditions = [];
    if ($report && isset($report['where_conditions'])) {
        $originalConditions = json_decode($report['where_conditions'], true);
    }
    
    // Store modifications to apply
    $modifications = [];
    
    foreach ($filters as $key => $value) {
        if (empty($value) && $value !== '0' && $value !== 0) {
            continue;
        }
        
        // Skip special keys (handled separately)
        if ($key === 'group_by' || $key === 'order_by' || $key === 'report_id') {
            continue;
        }
        
        // Skip operator keys (they're handled with their main key)
        if (strpos($key, 'OP_') === 0) {
            continue;
        }
        
        // Skip FROM/TO keys (handled with BETWEEN)
        if (strpos($key, 'FROM_') === 0 || strpos($key, 'TO_') === 0) {
            continue;
        }
        
        // Skip LIKE keys (handled with LIKE)
        if (strpos($key, 'LIKE_') === 0) {
            continue;
        }
        
        // Find matching original condition
        $originalCondition = null;
        foreach ($originalConditions as $cond) {
            if (isset($cond['column']) && $cond['column'] === $key) {
                $originalCondition = $cond;
                break;
            }
        }
        
        if (!$originalCondition) {
            continue;
        }
        
        // Build new condition based on type
        switch ($originalCondition['type'] ?? '') {
            case 'IN':
            case 'FUNCTION_IN':
                if (is_array($value) && !empty($value)) {
                    $escapedValues = array_map(function($v) use ($db) {
                        return "'" . $db->escapeString($v) . "'";
                    }, $value);
                    $newCondition = $originalCondition['column'] . " IN (" . implode(',', $escapedValues) . ")";
                    $modifications[] = [
                        'original' => $originalCondition['condition'],
                        'replacement' => $newCondition
                    ];
                }
                break;
                
            case 'BETWEEN':
            case 'FUNCTION_BETWEEN':
                $fromKey = 'FROM_' . $key;
                $toKey = 'TO_' . $key;
                
                $fromValue = isset($filters[$fromKey]) ? $filters[$fromKey] : '';
                $toValue = isset($filters[$toKey]) ? $filters[$toKey] : '';
                
                if (!empty($fromValue) && !empty($toValue)) {
                    $newCondition = $originalCondition['column'] . " BETWEEN '" . $db->escapeString($fromValue) . "' AND '" . $db->escapeString($toValue) . "'";
                    $modifications[] = [
                        'original' => $originalCondition['condition'],
                        'replacement' => $newCondition
                    ];
                }
                break;
                
            case 'LIKE':
            case 'FUNCTION_LIKE':
                $likeKey = 'LIKE_' . $key;
                $likeValue = isset($filters[$likeKey]) ? $filters[$likeKey] : $value;
                
                if (!empty($likeValue)) {
                    $newCondition = $originalCondition['column'] . " LIKE '%" . $db->escapeLikeString($likeValue) . "%'";
                    $modifications[] = [
                        'original' => $originalCondition['condition'],
                        'replacement' => $newCondition
                    ];
                }
                break;
                
            case 'COMPARISON':
            case 'FUNCTION_COMPARISON':
            case 'FIND_IN_SET':
                // Get operator from OP_key or use default
                $opKey = 'OP_' . $key;
                $operator = isset($filters[$opKey]) ? $filters[$opKey] : ($originalCondition['operator'] ?? '=');
                
                if ($value === 'NULL') {
                    $newCondition = $originalCondition['column'] . " IS NULL";
                } elseif ($value === 'NOT NULL') {
                    $newCondition = $originalCondition['column'] . " IS NOT NULL";
                } else {
                    // Escape the string properly WITHOUT adding quotes
                    $escapedValue = $db->escapeString($value);
                    
                    // Determine quote style from original condition
                    $quoteStyle = "'";
                    if (isset($originalCondition['original_value'])) {
                        $origVal = $originalCondition['original_value'];
                        // Check if original had double quotes
                        if (strlen($origVal) >= 2 && 
                            $origVal[0] === '"' && 
                            substr($origVal, -1) === '"') {
                            $quoteStyle = '"';
                        }
                    }
                    
                    $newCondition = $originalCondition['column'] . " " . $operator . " " . $quoteStyle . $escapedValue . $quoteStyle;
                }
                
                $modifications[] = [
                    'original' => $originalCondition['condition'],
                    'replacement' => $newCondition
                ];
                break;
                
            case 'IS_NULL':
            case 'FUNCTION_IS_NULL':
                if ($value === 'IS NULL' || $value === 'IS NOT NULL') {
                    $newCondition = $originalCondition['column'] . " " . $value;
                    $modifications[] = [
                        'original' => $originalCondition['condition'],
                        'replacement' => $newCondition
                    ];
                }
                break;
        }
    }
    
    // Apply all modifications to the SQL
    if (!empty($modifications)) {
        // Extract WHERE clause
        if (preg_match('/(WHERE\s+)(.*?)(?:\s+(?:GROUP BY|ORDER BY|LIMIT)|\s*$)/is', $sql, $matches)) {
            $whereKeyword = $matches[1];
            $originalWhereClause = $matches[2];
            $modifiedWhereClause = $originalWhereClause;
            
            // Apply each modification
            foreach ($modifications as $mod) {
                // Check if the original condition exists in the WHERE clause
                if (strpos($modifiedWhereClause, $mod['original']) !== false) {
                    // Simple string replacement
                    $modifiedWhereClause = str_replace($mod['original'], $mod['replacement'], $modifiedWhereClause);
                } else {
                    // Try to match without exact quotes (handle different quote styles)
                    $originalPattern = preg_quote($mod['original'], '/');
                    $originalPattern = str_replace(["\\'", '\\"'], ["['\"]", "['\"]"], $originalPattern);
                    
                    if (preg_match('/' . $originalPattern . '/', $modifiedWhereClause)) {
                        $modifiedWhereClause = preg_replace('/' . $originalPattern . '/', $mod['replacement'], $modifiedWhereClause, 1);
                    }
                }
            }
            
            // Replace the WHERE clause in the SQL
            $sql = str_replace(
                $whereKeyword . $originalWhereClause,
                $whereKeyword . $modifiedWhereClause,
                $sql
            );
        }
    }
    
    // Apply GROUP BY - FIXED: Handle array structure properly
    if (isset($filters['group_by']) && !empty($filters['group_by'])) {
        $groupByColumns = [];
        
        // Handle array input
        if (is_array($filters['group_by'])) {
            // Check if it's a simple indexed array
            if (isset($filters['group_by'][0])) {
                $groupByColumns = array_filter($filters['group_by']);
            } else {
                // It might be associative, extract values
                $groupByColumns = array_filter(array_values($filters['group_by']));
            }
        } else {
            $groupByColumns = [$filters['group_by']];
        }
        
        if (!empty($groupByColumns)) {
            $groupByString = 'GROUP BY ' . implode(', ', $groupByColumns);
            
            if (stripos($sql, 'GROUP BY') !== false) {
                // Replace existing GROUP BY
                $sql = preg_replace('/GROUP BY\s+.*?(?=\s+(?:ORDER BY|LIMIT)|\s*$)/is', 
                                  $groupByString, 
                                  $sql);
            } else {
                // Add new GROUP BY before ORDER BY if exists
                if (stripos($sql, 'ORDER BY') !== false) {
                    $sql = preg_replace('/(\s+ORDER BY)/i', ' ' . $groupByString . '$1', $sql);
                } else {
                    $sql .= ' ' . $groupByString;
                }
            }
        }
    }
    
    // Apply ORDER BY - FIXED: Handle array structure properly
    if (isset($filters['order_by']) && !empty($filters['order_by'])) {
        $orderByClauses = [];
        
        // Handle array input
        if (is_array($filters['order_by'])) {
            foreach ($filters['order_by'] as $item) {
                if (is_array($item) && !empty($item['column'])) {
                    $column = $item['column'];
                    $direction = isset($item['direction']) ? strtoupper(trim($item['direction'])) : 'ASC';
                    $orderByClauses[] = $column . ' ' . $direction;
                }
            }
        }
        
        if (!empty($orderByClauses)) {
            $orderByString = 'ORDER BY ' . implode(', ', $orderByClauses);
            
            if (stripos($sql, 'ORDER BY') !== false) {
                // Replace existing ORDER BY
                $sql = preg_replace('/ORDER BY\s+.*?(?=\s+LIMIT|\s*$)/is', 
                                  $orderByString, 
                                  $sql);
            } else {
                $sql .= ' ' . $orderByString;
            }
        }
    }
    
    return $sql;
}

    /**
     * Get all reports with pagination
     */
    public function getAllReports($perPage = 10, $page = 1)
    {
        return $this->paginate($perPage, 'default', $page);
    }
}