<?php

namespace App\Services;

class SqlExpressionService
{
    /**
     * Build complex CASE WHEN expression from mapping array
     */
    public function buildCaseExpression($field, $mappings, $else = null)
    {
        $caseSQL = "CASE";
        
        foreach ($mappings as $when => $then) {
            if (strpos($when, 'LIKE') !== false) {
                $caseSQL .= " WHEN {$field} {$when} THEN '{$then}'";
            } else {
                $caseSQL .= " WHEN {$field} = '{$when}' THEN '{$then}'";
            }
        }
        
        if ($else !== null) {
            $caseSQL .= " ELSE '{$else}'";
        }
        
        $caseSQL .= " END";
        
        return $caseSQL;
    }
    
    /**
     * Build nested CASE WHEN expressions
     */
    public function buildNestedCaseExpression($cases)
    {
        $sql = "CASE";
        
        foreach ($cases as $case) {
            if (isset($case['when']) && isset($case['then'])) {
                $sql .= " WHEN {$case['when']} THEN ";
                
                if (is_array($case['then'])) {
                    // Nested CASE
                    $sql .= $this->buildNestedCaseExpression($case['then']);
                } else {
                    $sql .= "'{$case['then']}'";
                }
            }
        }
        
        if (isset($cases['else'])) {
            $sql .= " ELSE '{$cases['else']}'";
        }
        
        $sql .= " END";
        
        return $sql;
    }
    
    /**
     * Build date expression
     */
    public function buildDateExpression($field, $operation = 'DATE', $format = null)
    {
        switch ($operation) {
            case 'DATE':
                return "DATE({$field})";
            case 'DATE_FORMAT':
                return $format ? "DATE_FORMAT({$field}, '{$format}')" : "DATE_FORMAT({$field}, '%Y-%m-%d')";
            case 'YEAR':
                return "YEAR({$field})";
            case 'MONTH':
                return "MONTH({$field})";
            case 'DAY':
                return "DAY({$field})";
            case 'DATEDIFF':
                return "DATEDIFF({$field})";
            default:
                return $field;
        }
    }
    
    /**
     * Build aggregate expression
     */
    public function buildAggregateExpression($field, $function, $alias = null)
    {
        $functions = ['SUM', 'COUNT', 'AVG', 'MIN', 'MAX'];
        
        if (!in_array(strtoupper($function), $functions)) {
            throw new \Exception("Invalid aggregate function: {$function}");
        }
        
        $expression = "{$function}({$field})";
        
        if ($alias) {
            $expression .= " AS {$alias}";
        }
        
        return $expression;
    }
    
    /**
     * Build CONCAT expression
     */
    public function buildConcatExpression($fields, $separator = ' ', $alias = null)
    {
        $fieldExpressions = [];
        
        foreach ($fields as $field) {
            $fieldExpressions[] = "COALESCE({$field}, '')";
        }
        
        $expression = "CONCAT(" . implode(", '{$separator}', ", $fieldExpressions) . ")";
        
        if ($alias) {
            $expression .= " AS {$alias}";
        }
        
        return $expression;
    }
    
    /**
     * Build COALESCE expression
     */
    public function buildCoalesceExpression($fields, $default = "''", $alias = null)
    {
        $fieldList = implode(', ', $fields);
        $expression = "COALESCE({$fieldList}, {$default})";
        
        if ($alias) {
            $expression .= " AS {$alias}";
        }
        
        return $expression;
    }
    
    /**
     * Build subquery expression
     */
    public function buildSubqueryExpression($sql, $alias = null)
    {
        $expression = "({$sql})";
        
        if ($alias) {
            $expression .= " AS {$alias}";
        }
        
        return $expression;
    }
    
    /**
     * Build IF expression
     */
    public function buildIfExpression($condition, $trueValue, $falseValue, $alias = null)
    {
        $expression = "IF({$condition}, '{$trueValue}', '{$falseValue}')";
        
        if ($alias) {
            $expression .= " AS {$alias}";
        }
        
        return $expression;
    }
    
    /**
     * Build mathematical expression
     */
    public function buildMathExpression($expression, $alias = null)
    {
        $validOperators = ['+', '-', '*', '/', '%'];
        
        // Basic validation
        foreach ($validOperators as $op) {
            if (strpos($expression, $op) !== false) {
                $expression = "({$expression})";
                break;
            }
        }
        
        if ($alias) {
            $expression .= " AS {$alias}";
        }
        
        return $expression;
    }
    
    /**
     * Parse SQL expression for UI builder
     */
    public function parseExpression($sql)
    {
        $tokens = [];
        $currentToken = '';
        $inString = false;
        $stringChar = '';
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            
            if (($char == "'" || $char == '"') && ($i == 0 || $sql[$i-1] != '\\')) {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                    if ($currentToken) {
                        $tokens[] = $currentToken;
                        $currentToken = '';
                    }
                    $currentToken .= $char;
                } elseif ($inString && $char == $stringChar) {
                    $inString = false;
                    $currentToken .= $char;
                    $tokens[] = $currentToken;
                    $currentToken = '';
                } else {
                    $currentToken .= $char;
                }
            } elseif ($inString) {
                $currentToken .= $char;
            } elseif (in_array($char, [' ', ',', '(', ')', '+', '-', '*', '/'])) {
                if ($currentToken) {
                    $tokens[] = $currentToken;
                    $currentToken = '';
                }
                if ($char != ' ') {
                    $tokens[] = $char;
                }
            } else {
                $currentToken .= $char;
            }
        }
        
        if ($currentToken) {
            $tokens[] = $currentToken;
        }
        
        return $tokens;
    }
}