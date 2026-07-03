<?php

namespace App\Services;

use App\Libraries\DynamicQueryBuilder;
use App\Models\ReportDefinitionModel;
use CodeIgniter\HTTP\ResponseInterface;

class ReportBuilderService
{
    protected $reportModel;
    protected $dynamicQueryBuilder;
    protected $sqlExpressionService;
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->reportModel = new ReportDefinitionModel();
        $this->dynamicQueryBuilder = new DynamicQueryBuilder($this->db);
        $this->sqlExpressionService = new SqlExpressionService($this->db);
    }

    /**
     * Generate report by ID
     */
    public function generateReport(int $reportId, array $parameters = [], array $options = []): array
    {
        // Load report definition
        $definition = $this->reportModel->getCompleteDefinition($reportId);
        
        if (!$definition) {
            throw new \RuntimeException("Report with ID {$reportId} not found");
        }

        // Merge runtime parameters with defaults
        $parameters = $this->mergeParameters($definition, $parameters);

        // Validate parameters
        $this->validateParameters($definition, $parameters);

        // Build and execute query
        $this->dynamicQueryBuilder
            ->setReportDefinition($definition)
            ->setParameters($parameters);

        // Enable raw SQL mode for complex reports
        if ($this->isComplexReport($definition)) {
            $this->dynamicQueryBuilder->enableRawSqlMode();
        }

        $data = $this->dynamicQueryBuilder->execute($options);
        
        // Format data if needed
        $data = $this->formatReportData($data, $definition);

        return [
            'data' => $data,
            'total' => $this->dynamicQueryBuilder->getCount(),
            'definition' => $definition,
            'sql' => $options['debug'] ? $this->dynamicQueryBuilder->getSql() : null
        ];
    }

    /**
     * Generate report from template
     */
    public function generateFromTemplate(string $templateName, array $parameters = [], array $options = []): array
    {
        $template = $this->reportModel->getTemplateByName($templateName);
        
        if (!$template) {
            throw new \RuntimeException("Template '{$templateName}' not found");
        }

        return $this->generateReport($template['id'], $parameters, $options);
    }

    /**
     * Check if report is complex (needs raw SQL)
     */
    protected function isComplexReport(array $definition): bool
    {
        $complexIndicators = [
            'CASE WHEN' => false,
            'EXISTS' => false,
            'subquery' => false,
            'complex_joins' => false
        ];

        // Check columns for complex expressions
        foreach ($definition['columns'] ?? [] as $column) {
            $expression = strtoupper($column['column_expression']);
            if (strpos($expression, 'CASE') !== false) {
                $complexIndicators['CASE WHEN'] = true;
            }
            if (strpos($expression, 'SELECT') !== false) {
                $complexIndicators['subquery'] = true;
            }
        }

        // Check filters for EXISTS
        foreach ($definition['filters'] ?? [] as $filter) {
            if (in_array($filter['condition_type'], ['EXISTS', 'NOT EXISTS'])) {
                $complexIndicators['EXISTS'] = true;
            }
            if (strpos(strtoupper($filter['condition_expression']), 'SELECT') !== false) {
                $complexIndicators['subquery'] = true;
            }
        }

        // Check for multiple/complex joins
        if (count($definition['joins'] ?? []) > 3) {
            $complexIndicators['complex_joins'] = true;
        }

        return in_array(true, $complexIndicators, true);
    }

    /**
     * Merge runtime parameters with defaults
     */
    protected function mergeParameters(array $definition, array $parameters): array
    {
        foreach ($definition['parameters'] ?? [] as $param) {
            $key = $param['parameter_key'];
            if (!isset($parameters[$key]) && isset($param['default_value'])) {
                $parameters[$key] = $param['default_value'];
            }
        }

        return $parameters;
    }

    /**
     * Validate parameters
     */
    protected function validateParameters(array $definition, array $parameters): void
    {
        foreach ($definition['parameters'] ?? [] as $param) {
            $key = $param['parameter_key'];
            
            if ($param['is_required'] && !isset($parameters[$key])) {
                throw new \InvalidArgumentException("Parameter '{$key}' is required");
            }

            if (isset($parameters[$key])) {
                $this->validateParameterType($param, $parameters[$key]);
            }
        }
    }

    /**
     * Validate parameter type
     */
    protected function validateParameterType(array $param, $value): void
    {
        $type = $param['parameter_type'];
        
        switch ($type) {
            case 'integer':
                if (!is_int($value) && !ctype_digit((string) $value)) {
                    throw new \InvalidArgumentException("Parameter '{$param['parameter_key']}' must be an integer");
                }
                break;
                
            case 'decimal':
            case 'float':
                if (!is_numeric($value)) {
                    throw new \InvalidArgumentException("Parameter '{$param['parameter_key']}' must be a number");
                }
                break;
                
            case 'date':
                if (!strtotime($value) && $value !== '') {
                    throw new \InvalidArgumentException("Parameter '{$param['parameter_key']}' must be a valid date");
                }
                break;
                
            case 'datetime':
                if (!strtotime($value) && $value !== '') {
                    throw new \InvalidArgumentException("Parameter '{$param['parameter_key']}' must be a valid datetime");
                }
                break;
                
            case 'boolean':
                if (!is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false'], true)) {
                    throw new \InvalidArgumentException("Parameter '{$param['parameter_key']}' must be boolean");
                }
                break;
                
            case 'array':
                if (!is_array($value)) {
                    throw new \InvalidArgumentException("Parameter '{$param['parameter_key']}' must be an array");
                }
                break;
        }
    }

    /**
     * Format report data based on column definitions
     */
    protected function formatReportData(array $data, array $definition): array
    {
        $columnFormats = [];
        
        // Build format map
        foreach ($definition['columns'] ?? [] as $column) {
            if (!empty($column['format_pattern'])) {
                $columnFormats[$column['alias']] = $column['format_pattern'];
            }
        }
        
        if (empty($columnFormats)) {
            return $data;
        }
        
        foreach ($data as &$row) {
            foreach ($columnFormats as $column => $format) {
                if (isset($row[$column])) {
                    $row[$column] = $this->applyFormat($row[$column], $format);
                }
            }
        }
        
        return $data;
    }

    /**
     * Apply format to value
     */
    protected function applyFormat($value, string $format)
    {
        if ($value === null) {
            return $value;
        }
        
        switch ($format) {
            case 'currency':
                return '$' . number_format((float) $value, 2);
                
            case 'percent':
                return round((float) $value * 100, 2) . '%';
                
            case 'number':
                return number_format((float) $value);
                
            case strpos($format, 'date:') === 0:
                $dateFormat = substr($format, 5);
                $timestamp = strtotime($value);
                return $timestamp ? date($dateFormat, $timestamp) : $value;
                
            case strpos($format, 'decimal:') === 0:
                $decimals = (int) substr($format, 8);
                return number_format((float) $value, $decimals);
                
            default:
                return $value;
        }
    }

    /**
     * Export report to CSV
     */
    public function exportToCsv(int $reportId, array $parameters = [], string $filename = null): ResponseInterface
    {
        $result = $this->generateReport($reportId, $parameters);
        $data = $result['data'];
        $definition = $result['definition'];
        
        $filename = $filename ?: 'report_' . date('Y-m-d_H-i-s') . '.csv';
        
        $response = service('response');
        
        // Set headers
        $response->setHeader('Content-Type', 'text/csv');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setHeader('Pragma', 'no-cache');
        $response->setHeader('Expires', '0');
        
        // Create CSV content
        $output = fopen('php://output', 'w');
        
        // Add headers
        $headers = [];
        foreach ($definition['columns'] ?? [] as $column) {
            $headers[] = $column['alias'];
        }
        fputcsv($output, $headers);
        
        // Add data rows
        foreach ($data as $row) {
            $csvRow = [];
            foreach ($headers as $header) {
                $csvRow[] = $row[$header] ?? '';
            }
            fputcsv($output, $csvRow);
        }
        
        fclose($output);
        
        return $response;
    }

    /**
     * Export report to Excel (using PhpSpreadsheet)
     */
    public function exportToExcel(int $reportId, array $parameters = [], string $filename = null): ResponseInterface
    {
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            throw new \RuntimeException('PhpSpreadsheet is not installed. Run: composer require phpoffice/phpspreadsheet');
        }
        
        $result = $this->generateReport($reportId, $parameters);
        $data = $result['data'];
        $definition = $result['definition'];
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $col = 1;
        foreach ($definition['columns'] ?? [] as $column) {
            $sheet->setCellValueByColumnAndRow($col, 1, $column['alias']);
            $col++;
        }
        
        // Set data
        $row = 2;
        foreach ($data as $dataRow) {
            $col = 1;
            foreach ($definition['columns'] ?? [] as $column) {
                $value = $dataRow[$column['alias']] ?? '';
                $sheet->setCellValueByColumnAndRow($col, $row, $value);
                $col++;
            }
            $row++;
        }
        
        // Auto-size columns
        foreach (range(1, count($definition['columns'] ?? [])) as $column) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }
        
        $filename = $filename ?: 'report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        $response = service('response');
        $response->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setHeader('Cache-Control', 'max-age=0');
        
        ob_start();
        $writer->save('php://output');
        $response->setBody(ob_get_clean());
        
        return $response;
    }

    /**
     * Create report template from existing report
     */
    public function createTemplate(int $reportId, string $templateName, string $description = null): bool
    {
        return $this->reportModel->createTemplate($reportId, $templateName, $description);
    }

    /**
     * Get available parameters for a report
     */
    public function getReportParameters(int $reportId): array
    {
        $definition = $this->reportModel->getCompleteDefinition($reportId);
        return $definition['parameters'] ?? [];
    }
}