<?php

namespace App\Services;

use App\Libraries\EnhancedQueryBuilder;
use App\Models\ReportModel;

class EnhancedReportBuilderService extends ReportBuilderService
{
    protected $enhancedQueryBuilder;
    
    public function __construct()
    {
        parent::__construct();
        $this->enhancedQueryBuilder = new EnhancedQueryBuilder();
    }
    
    /**
     * Execute report with complex features
     */
    public function executeComplexReport($reportId, $parameters = [], $options = [])
    {
        $report = $this->reportModel->getReportWithDetails($reportId);
        
        if (!$report) {
            throw new \Exception("Report not found");
        }
        
        // Apply advanced parameter transformations
        $parameters = $this->transformComplexParameters($parameters, $report);
        
        // Build and execute enhanced query
        $query = $this->enhancedQueryBuilder->buildEnhancedQuery($report, $parameters);
        
        $db = \Config\Database::connect();
        
        // Execute with pagination
        $limit = $options['limit'] ?? null;
        $offset = $options['offset'] ?? 0;
        
        $finalSql = $query['sql'];
        $queryParams = $query['params'];
        
        if ($limit) {
            $finalSql .= " LIMIT ? OFFSET ?";
            $queryParams[] = $limit;
            $queryParams[] = $offset;
        }
        
        $result = $db->query($finalSql, $queryParams)->getResultArray();
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM ({$query['sql']}) as count_table";
        $countResult = $db->query($countSql, $query['params'])->getRow();
        $totalRows = $countResult->total;
        
        // Apply advanced post-processing
        if (!empty($options['post_process'])) {
            $result = $this->advancedPostProcess($result, $options['post_process'], $report);
        }
        
        return [
            'data' => $result,
            'total' => $totalRows,
            'sql' => $finalSql,
            'params' => $queryParams
        ];
    }
    
    /**
     * Transform complex parameters including arrays, dates, etc.
     */
    protected function transformComplexParameters($parameters, $report)
    {
        $transformed = [];
        
        foreach ($parameters as $key => $value) {
            if (is_string($value)) {
                // Handle date ranges
                if (strpos($key, '_date_range') !== false) {
                    $dates = explode(' to ', $value);
                    if (count($dates) === 2) {
                        $transformed[$key] = [
                            'start' => trim($dates[0]),
                            'end' => trim($dates[1])
                        ];
                    }
                }
                // Handle comma-separated lists
                elseif (strpos($value, ',') !== false) {
                    $transformed[$key] = array_map('trim', explode(',', $value));
                }
                else {
                    $transformed[$key] = $this->escapeValue($value);
                }
            } elseif (is_array($value)) {
                // Process array parameters
                $transformed[$key] = array_map([$this, 'escapeValue'], $value);
            } else {
                $transformed[$key] = $this->escapeValue($value);
            }
        }
        
        // Apply parameter defaults and validations
        if (!empty($report['parameters'])) {
            foreach ($report['parameters'] as $param) {
                $paramName = $param['parameter_name'];
                
                if (!isset($transformed[$paramName]) && $param['default_value']) {
                    $transformed[$paramName] = $param['default_value'];
                }
                
                // Validate parameter
                if (isset($transformed[$paramName])) {
                    $transformed[$paramName] = $this->validateParameter(
                        $transformed[$paramName], 
                        $param['data_type'], 
                        $param
                    );
                }
            }
        }
        
        return $transformed;
    }
    
    /**
     * Validate parameter based on data type
     */
    protected function validateParameter($value, $dataType, $param)
    {
        switch ($dataType) {
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'date':
                if (!strtotime($value)) {
                    throw new \Exception("Invalid date value for parameter: {$param['parameter_label']}");
                }
                return date('Y-m-d', strtotime($value));
            case 'datetime':
                if (!strtotime($value)) {
                    throw new \Exception("Invalid datetime value for parameter: {$param['parameter_label']}");
                }
                return date('Y-m-d H:i:s', strtotime($value));
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'array':
                if (!is_array($value)) {
                    $value = [$value];
                }
                return $value;
            default:
                return $value;
        }
    }
    
    /**
     * Advanced post-processing with calculations
     */
    protected function advancedPostProcess($data, $processors, $report)
    {
        foreach ($processors as $processor) {
            switch ($processor['type']) {
                case 'running_total':
                    $data = $this->calculateRunningTotal($data, $processor);
                    break;
                case 'percentage_of_total':
                    $data = $this->calculatePercentageOfTotal($data, $processor);
                    break;
                case 'rank_calculation':
                    $data = $this->calculateRanks($data, $processor);
                    break;
                case 'moving_average':
                    $data = $this->calculateMovingAverage($data, $processor);
                    break;
                case 'cumulative_sum':
                    $data = $this->calculateCumulativeSum($data, $processor);
                    break;
                case 'pivot_table':
                    $data = $this->createPivotTable($data, $processor);
                    break;
            }
        }
        
        return $data;
    }
    
    /**
     * Calculate running totals
     */
    protected function calculateRunningTotal($data, $processor)
    {
        $runningTotal = 0;
        $groupField = $processor['group_field'] ?? null;
        $valueField = $processor['value_field'];
        $resultField = $processor['result_field'] ?? 'running_total';
        
        $currentGroup = null;
        
        foreach ($data as &$row) {
            if ($groupField && $row[$groupField] != $currentGroup) {
                $runningTotal = 0;
                $currentGroup = $row[$groupField];
            }
            
            $runningTotal += (float) ($row[$valueField] ?? 0);
            $row[$resultField] = $runningTotal;
        }
        
        return $data;
    }
    
    /**
     * Calculate percentage of total
     */
    protected function calculatePercentageOfTotal($data, $processor)
    {
        $valueField = $processor['value_field'];
        $resultField = $processor['result_field'] ?? 'percentage';
        $groupField = $processor['group_field'] ?? null;
        
        // Calculate totals
        $totals = [];
        if ($groupField) {
            foreach ($data as $row) {
                $group = $row[$groupField];
                $totals[$group] = ($totals[$group] ?? 0) + (float) ($row[$valueField] ?? 0);
            }
        } else {
            $total = array_sum(array_column($data, $valueField));
        }
        
        // Calculate percentages
        foreach ($data as &$row) {
            if ($groupField) {
                $groupTotal = $totals[$row[$groupField]] ?? 1;
                $row[$resultField] = $groupTotal > 0 ? 
                    ((float) ($row[$valueField] ?? 0) / $groupTotal) * 100 : 0;
            } else {
                $row[$resultField] = $total > 0 ? 
                    ((float) ($row[$valueField] ?? 0) / $total) * 100 : 0;
            }
            $row[$resultField] = round($row[$resultField], 2);
        }
        
        return $data;
    }
    
    /**
     * Calculate ranks
     */
    protected function calculateRanks($data, $processor)
    {
        $valueField = $processor['value_field'];
        $resultField = $processor['result_field'] ?? 'rank';
        $direction = $processor['direction'] ?? 'desc';
        $groupField = $processor['group_field'] ?? null;
        
        // Sort data
        usort($data, function($a, $b) use ($valueField, $direction) {
            $valueA = $a[$valueField] ?? 0;
            $valueB = $b[$valueField] ?? 0;
            
            if ($direction === 'desc') {
                return $valueB <=> $valueA;
            } else {
                return $valueA <=> $valueB;
            }
        });
        
        // Assign ranks
        $rank = 0;
        $lastValue = null;
        $skipRank = 0;
        $currentGroup = null;
        
        foreach ($data as &$row) {
            if ($groupField && $row[$groupField] != $currentGroup) {
                $rank = 0;
                $lastValue = null;
                $skipRank = 0;
                $currentGroup = $row[$groupField];
            }
            
            $currentValue = $row[$valueField] ?? 0;
            
            if ($currentValue === $lastValue) {
                $skipRank++;
            } else {
                $rank += 1 + $skipRank;
                $skipRank = 0;
            }
            
            $row[$resultField] = $rank;
            $lastValue = $currentValue;
        }
        
        return $data;
    }
}