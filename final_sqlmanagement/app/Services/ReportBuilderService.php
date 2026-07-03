<?php

namespace App\Services;

use App\Libraries\DynamicQueryBuilder;
use App\Models\ReportModel;

class ReportBuilderService
{
    protected $reportModel;
    protected $queryBuilder;
    
    public function __construct()
    {
        $this->reportModel = new ReportModel();
        $this->queryBuilder = new DynamicQueryBuilder();
    }
    
    /**
     * Execute report with advanced features
     */
    public function executeDynamicReport($reportId, $parameters = [], $options = [])
    {
        // Get report definition
        $report = $this->reportModel->getReportWithDetails($reportId);
        
        if (!$report) {
            throw new \Exception("Report not found");
        }
        
        // Apply parameter transformations
        $parameters = $this->transformParameters($parameters, $report);
        
        // Build and execute query
        $result = $this->reportModel->executeReport(
            $reportId, 
            $parameters, 
            $options['limit'] ?? null, 
            $options['offset'] ?? 0
        );
        
        // Apply post-processing if needed
        if (!empty($options['post_process'])) {
            $result['data'] = $this->postProcessData($result['data'], $options['post_process']);
        }
        
        return $result;
    }
    
    /**
     * Transform parameters for SQL
     */
    protected function transformParameters($parameters, $report)
    {
        $transformed = [];
        
        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                // Handle array parameters (e.g., IN clause)
                $transformed[$key] = implode(',', array_map([$this, 'escapeValue'], $value));
            } else {
                $transformed[$key] = $this->escapeValue($value);
            }
        }
        
        // Apply default values from report definition
        if (!empty($report['parameters'])) {
            foreach ($report['parameters'] as $param) {
                if (!isset($transformed[$param['parameter_name']]) && $param['default_value']) {
                    $transformed[$param['parameter_name']] = $param['default_value'];
                }
            }
        }
        
        return $transformed;
    }
    
    /**
     * Escape value for SQL
     */
    protected function escapeValue($value)
    {
        $db = \Config\Database::connect();
        return $db->escape($value);
    }
    
    /**
     * Post-process data (formatting, calculations, etc.)
     */
    protected function postProcessData($data, $processors)
    {
        foreach ($processors as $processor) {
            switch ($processor['type']) {
                case 'date_format':
                    foreach ($data as &$row) {
                        if (isset($row[$processor['field']])) {
                            $row[$processor['field']] = date($processor['format'], strtotime($row[$processor['field']]));
                        }
                    }
                    break;
                    
                case 'number_format':
                    foreach ($data as &$row) {
                        if (isset($row[$processor['field']])) {
                            $row[$processor['field']] = number_format(
                                $row[$processor['field']], 
                                $processor['decimals'] ?? 2,
                                $processor['decimal_separator'] ?? '.',
                                $processor['thousands_separator'] ?? ','
                            );
                        }
                    }
                    break;
                    
                case 'custom_calculation':
                    foreach ($data as &$row) {
                        $row = $this->applyCustomCalculation($row, $processor);
                    }
                    break;
            }
        }
        
        return $data;
    }
    
    /**
     * Apply custom calculation to row
     */
    protected function applyCustomCalculation($row, $processor)
    {
        // Example: Calculate percentage
        if ($processor['calculation'] === 'percentage') {
            $numerator = $row[$processor['numerator_field']] ?? 0;
            $denominator = $row[$processor['denominator_field']] ?? 1;
            $row[$processor['result_field']] = ($denominator != 0) ? ($numerator / $denominator) * 100 : 0;
        }
        
        return $row;
    }
    
    /**
     * Generate CSV from report data
     */
    public function generateCSV($reportId, $parameters = [], $filename = 'report.csv')
    {
        $result = $this->executeDynamicReport($reportId, $parameters);
        
        $csv = '';
        
        // Add headers
        if (!empty($result['data'])) {
            $headers = array_keys($result['data'][0]);
            $csv .= implode(',', $headers) . "\n";
            
            // Add data rows
            foreach ($result['data'] as $row) {
                $csv .= implode(',', array_map(function($value) {
                    // Escape commas and quotes
                    $value = str_replace('"', '""', $value);
                    return '"' . $value . '"';
                }, $row)) . "\n";
            }
        }
        
        return [
            'filename' => $filename,
            'content' => $csv,
            'count' => count($result['data'])
        ];
    }
    
    /**
     * Generate Excel (using PhpSpreadsheet)
     */
    // public function generateExcel($reportId, $parameters = [], $filename = 'report.xlsx')
    // {
    //     $result = $this->executeDynamicReport($reportId, $parameters);
        
    //     // Check if PhpSpreadsheet is available
    //     if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    //         throw new \Exception('PhpSpreadsheet is not installed. Run: composer require phpoffice/phpspreadsheet');
    //     }
        
    //     $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    //     $sheet = $spreadsheet->getActiveSheet();
        
    //     // Add headers
    //     if (!empty($result['data'])) {
    //         $headers = array_keys($result['data'][0]);
    //         $col = 'A';
    //         foreach ($headers as $header) {
    //             $sheet->setCellValue($col . '1', $header);
    //             $col++;
    //         }
            
    //         // Add data rows
    //         $row = 2;
    //         foreach ($result['data'] as $dataRow) {
    //             $col = 'A';
    //             foreach ($dataRow as $value) {
    //                 $sheet->setCellValue($col . $row, $value);
    //                 $col++;
    //             }
    //             $row++;
    //         }
            
    //         // Auto-size columns
    //         foreach (range('A', $col) as $columnID) {
    //             $sheet->getColumnDimension($columnID)->setAutoSize(true);
    //         }
    //     }
        
    //     // Save to temporary file
    //     $tempFile = WRITEPATH . 'temp/' . $filename;
    //     $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    //     $writer->save($tempFile);
        
    //     return [
    //         'filename' => $filename,
    //         'path' => $tempFile,
    //         'count' => count($result['data'])
    //     ];
    // }

    public function generateExcel($reportId, $parameters = [], $filename = 'report.xlsx')
    {
        $result = $this->executeDynamicReport($reportId, $parameters);
        
        // Check if PhpSpreadsheet is available
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            throw new \Exception('PhpSpreadsheet is not installed. Run: composer require phpoffice/phpspreadsheet');
        }
        
        // Ensure temp directory exists
        $tempDir = WRITEPATH . 'temp/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        
        // Clean up old files (optional - keep last 10 files)
        $this->cleanupOldFiles($tempDir);
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Add headers
        if (!empty($result['data'])) {
            $headers = array_keys($result['data'][0]);
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '1', $header);
                // Style the header
                $sheet->getStyle($col . '1')->getFont()->setBold(true);
                $sheet->getStyle($col . '1')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE0E0E0');
                $col++;
            }
            
            // Add data rows
            $row = 2;
            foreach ($result['data'] as $dataRow) {
                $col = 'A';
                foreach ($dataRow as $value) {
                    $sheet->setCellValue($col . $row, $value);
                    $col++;
                }
                $row++;
            }
            
            // Auto-size columns
            $lastColumn = $sheet->getHighestColumn();
            for ($col = 'A'; $col <= $lastColumn; $col++) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Add borders
            $lastRow = $sheet->getHighestRow();
            $styleArray = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ];
            $sheet->getStyle('A1:' . $lastColumn . $lastRow)->applyFromArray($styleArray);
            
            // Freeze header row
            $sheet->freezePane('A2');
        }
        
        // Save to temporary file
        $tempFile = rtrim($tempDir, '/') . '/' . uniqid('report_', true) . '.xlsx';
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        try {
            $writer->save($tempFile);
            
            // Verify file was created
            if (!file_exists($tempFile)) {
                throw new \Exception('Failed to create Excel file');
            }
            
        } catch (\Exception $e) {
            // Clean up on error
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
            throw new \Exception('Excel generation failed: ' . $e->getMessage());
        }
        
        return [
            'filename' => $filename,
            'path' => $tempFile,
            'count' => count($result['data'])
        ];
    }

    private function cleanupOldFiles($directory, $keepCount = 10)
    {
        $files = glob($directory . 'report_*.xlsx');
        
        if (count($files) > $keepCount) {
            // Sort by modification time
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Remove oldest files
            $filesToDelete = array_slice($files, 0, count($files) - $keepCount);
            foreach ($filesToDelete as $file) {
                @unlink($file);
            }
        }
    }
    
    /**
     * Validate report parameters
     */
    public function validateParameters($parameters, $report)
    {
        $errors = [];
        
        if (!empty($report['parameters'])) {
            foreach ($report['parameters'] as $param) {
                $paramName = $param['parameter_name'];
                
                // Check required parameters
                if ($param['required'] && !isset($parameters[$paramName])) {
                    $errors[] = "Parameter '{$param['parameter_label']}' is required";
                }
                
                // Validate data type
                if (isset($parameters[$paramName])) {
                    $value = $parameters[$paramName];
                    switch ($param['data_type']) {
                        case 'integer':
                            if (!is_numeric($value)) {
                                $errors[] = "Parameter '{$param['parameter_label']}' must be a number";
                            }
                            break;
                        case 'date':
                            if (!strtotime($value)) {
                                $errors[] = "Parameter '{$param['parameter_label']}' must be a valid date";
                            }
                            break;
                        case 'array':
                            if (!is_array($value)) {
                                $errors[] = "Parameter '{$param['parameter_label']}' must be an array";
                            }
                            break;
                    }
                }
            }
        }
        
        return $errors;
    }
}