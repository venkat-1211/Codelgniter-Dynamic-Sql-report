<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

class SqlExpressionService
{
    protected $db;
    
    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
    }

    /**
     * Build CASE WHEN expression
     */
    public function buildCaseExpression(array $cases, string $elseValue = null, string $alias = null): string
    {
        $expression = 'CASE';
        
        foreach ($cases as $when => $then) {
            $whenEscaped = $this->escapeCondition($when);
            $thenEscaped = $this->escapeValue($then);
            $expression .= " WHEN {$whenEscaped} THEN {$thenEscaped}";
        }
        
        if ($elseValue !== null) {
            $elseEscaped = $this->escapeValue($elseValue);
            $expression .= " ELSE {$elseEscaped}";
        }
        
        $expression .= ' END';
        
        if ($alias) {
            $aliasEscaped = $this->db->escapeIdentifiers($alias);
            $expression .= " AS {$aliasEscaped}";
        }
        
        return $expression;
    }

    /**
     * Build date difference expression
     */
    public function buildDateDiff(string $date1, string $date2, string $unit = 'DAY', string $alias = null): string
    {
        $unit = strtoupper($unit);
        $validUnits = ['MICROSECOND', 'SECOND', 'MINUTE', 'HOUR', 'DAY', 'WEEK', 'MONTH', 'QUARTER', 'YEAR'];
        
        if (!in_array($unit, $validUnits)) {
            $unit = 'DAY';
        }
        
        $date1Escaped = $this->escapeField($date1);
        $date2Escaped = $this->escapeField($date2);
        
        $expression = "TIMESTAMPDIFF({$unit}, {$date1Escaped}, {$date2Escaped})";
        
        if ($alias) {
            $aliasEscaped = $this->db->escapeIdentifiers($alias);
            $expression .= " AS {$aliasEscaped}";
        }
        
        return $expression;
    }

    /**
     * Build COALESCE expression
     */
    public function buildCoalesce(array $fields, string $alias = null): string
    {
        $escapedFields = array_map([$this, 'escapeField'], $fields);
        $expression = 'COALESCE(' . implode(', ', $escapedFields) . ')';
        
        if ($alias) {
            $aliasEscaped = $this->db->escapeIdentifiers($alias);
            $expression .= " AS {$aliasEscaped}";
        }
        
        return $expression;
    }

    /**
     * Build CONCAT expression
     */
    public function buildConcat(array $fields, string $separator = null, string $alias = null): string
    {
        $escapedFields = array_map([$this, 'escapeField'], $fields);
        
        if ($separator !== null) {
            $separatorEscaped = $this->db->escape($separator);
            $expression = "CONCAT_WS({$separatorEscaped}, " . implode(', ', $escapedFields) . ')';
        } else {
            $expression = 'CONCAT(' . implode(', ', $escapedFields) . ')';
        }
        
        if ($alias) {
            $aliasEscaped = $this->db->escapeIdentifiers($alias);
            $expression .= " AS {$aliasEscaped}";
        }
        
        return $expression;
    }

    /**
     * Build subquery expression
     */
    public function buildSubquery(string $sql, string $alias = null): string
    {
        $expression = "({$sql})";
        
        if ($alias) {
            $aliasEscaped = $this->db->escapeIdentifiers($alias);
            $expression .= " AS {$aliasEscaped}";
        }
        
        return $expression;
    }

    /**
     * Build EXISTS expression
     */
    public function buildExists(string $subquery, bool $not = false): string
    {
        $prefix = $not ? 'NOT ' : '';
        return "{$prefix}EXISTS ({$subquery})";
    }

    /**
     * Build date format expression
     */
    public function buildDateFormat(string $field, string $format, string $alias = null): string
    {
        $fieldEscaped = $this->escapeField($field);
        $formatEscaped = $this->db->escape($format);
        
        $expression = "DATE_FORMAT({$fieldEscaped}, {$formatEscaped})";
        
        if ($alias) {
            $aliasEscaped = $this->db->escapeIdentifiers($alias);
            $expression .= " AS {$aliasEscaped}";
        }
        
        return $expression;
    }

    /**
     * Build ROUND expression
     */
    public function buildRound(string $field, int $decimals = 2, string $alias = null): string
    {
        $fieldEscaped = $this->escapeField($field);
        $expression = "ROUND({$fieldEscaped}, {$decimals})";
        
        if ($alias) {
            $aliasEscaped = $this->db->escapeIdentifiers($alias);
            $expression .= " AS {$aliasEscaped}";
        }
        
        return $expression;
    }

    /**
     * Build aggregate expression (SUM, AVG, COUNT, MIN, MAX)
     */
    public function buildAggregate(string $function, string $field, bool $distinct = false, string $alias = null): string
    {
        $function = strtoupper($function);
        $validFunctions = ['SUM', 'AVG', 'COUNT', 'MIN', 'MAX'];
        
        if (!in_array($function, $validFunctions)) {
            $function = 'COUNT';
        }
        
        $fieldEscaped = $this->escapeField($field);
        $distinctStr = $distinct ? 'DISTINCT ' : '';
        
        $expression = "{$function}({$distinctStr}{$fieldEscaped})";
        
        if ($alias) {
            $aliasEscaped = $this->db->escapeIdentifiers($alias);
            $expression .= " AS {$aliasEscaped}";
        }
        
        return $expression;
    }

    /**
     * Escape field/column name
     */
    protected function escapeField(string $field): string
    {
        // If it's already a function or complex expression, don't escape
        if (strpos($field, '(') !== false || strpos($field, ' ') !== false) {
            return $field;
        }
        
        return $this->db->escapeIdentifiers($field);
    }

    /**
     * Escape value for SQL
     */
    protected function escapeValue($value): string
    {
        if (is_numeric($value)) {
            return (string) $value;
        }
        
        if ($value === null) {
            return 'NULL';
        }
        
        if ($value === true) {
            return 'TRUE';
        }
        
        if ($value === false) {
            return 'FALSE';
        }
        
        return $this->db->escape($value);
    }

    /**
     * Escape condition
     */
    protected function escapeCondition(string $condition): string
    {
        // Don't escape complex conditions
        if (strpos($condition, '=') !== false || 
            strpos($condition, '>') !== false || 
            strpos($condition, '<') !== false ||
            strpos($condition, 'LIKE') !== false ||
            strpos($condition, 'IN') !== false ||
            strpos($condition, 'BETWEEN') !== false) {
            return $condition;
        }
        
        return $this->db->escape($condition);
    }

    /**
     * Parse and validate SQL expression
     */
    public function validateExpression(string $expression): bool
    {
        // Remove allowed SQL functions and keywords
        $safeExpression = preg_replace([
            '/\b(SELECT|FROM|WHERE|JOIN|ON|GROUP BY|HAVING|ORDER BY|LIMIT)\b/i',
            '/\b(SUM|AVG|COUNT|MIN|MAX|COALESCE|CONCAT|DATE_FORMAT|TIMESTAMPDIFF|ROUND)\b/i',
            '/\b(CASE|WHEN|THEN|ELSE|END)\b/i',
            '/\b(INNER|LEFT|RIGHT|OUTER|JOIN)\b/i',
            '/\b(AND|OR|NOT|IN|BETWEEN|LIKE|IS|NULL)\b/i',
            '/`[^`]+`/', // Backticks
            '/\'[^\']*\'/', // Single quotes
            '/"[^"]*"/', // Double quotes
            '/\d+/', // Numbers
            '/\s+/', // Whitespace
        ], '', $expression);

        // Check for suspicious characters
        if (preg_match('/[;\(\)=<>!&|\-\+\*\/%]/', $safeExpression)) {
            return false;
        }

        return true;
    }
}