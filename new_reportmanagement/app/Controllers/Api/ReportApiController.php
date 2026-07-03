<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\ReportBuilderService;
use CodeIgniter\API\ResponseTrait;

class ReportApiController extends BaseController
{
    use ResponseTrait;
    
    protected $reportBuilderService;
    protected $reportModel;
    protected $definitionModel;
    
    public function __construct()
    {
        $this->reportBuilderService = new ReportBuilderService();
        $this->reportModel = new \App\Models\ReportModel();
        $this->definitionModel = new \App\Models\ReportDefinitionModel();
        
        helper('text');
    }
    
    /**
     * Generate report via API
     * GET /api/reports/{id}/generate
     */
    public function generate($reportId)
    {
        try {
            // Get parameters
            $parameters = $this->request->getGet();
            
            // Pagination
            $page = (int) ($this->request->getGet('page') ?: 1);
            $perPage = (int) ($this->request->getGet('per_page') ?: 50);
            $limit = (int) ($this->request->getGet('limit') ?: $perPage);
            $offset = ($page - 1) * $perPage;
            
            // Options
            $includeSql = $this->request->getGet('include_sql') === 'true';
            $includeDefinition = $this->request->getGet('include_definition') === 'true';
            
            $options = [
                'limit' => $limit,
                'offset' => $offset,
                'debug' => $includeSql
            ];
            
            // Generate report
            $result = $this->reportBuilderService->generateReport($reportId, $parameters, $options);
            
            $response = [
                'success' => true,
                'data' => [
                    'report_id' => $reportId,
                    'data' => $result['data'],
                    'pagination' => [
                        'total' => $result['total'],
                        'per_page' => $perPage,
                        'current_page' => $page,
                        'total_pages' => ceil($result['total'] / $perPage)
                    ]
                ]
            ];
            
            if ($includeSql) {
                $response['data']['sql'] = $result['sql'] ?? null;
            }
            
            if ($includeDefinition) {
                $response['data']['definition'] = $result['definition'];
            }
            
            return $this->respond($response);
            
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
    
    /**
     * Generate report from template via API
     * GET /api/reports/templates/{name}/generate
     */
    public function generateFromTemplate($templateName)
    {
        try {
            $parameters = $this->request->getGet();
            
            $page = (int) ($this->request->getGet('page') ?: 1);
            $perPage = (int) ($this->request->getGet('per_page') ?: 50);
            $limit = (int) ($this->request->getGet('limit') ?: $perPage);
            $offset = ($page - 1) * $perPage;
            
            $options = [
                'limit' => $limit,
                'offset' => $offset
            ];
            
            $result = $this->reportBuilderService->generateFromTemplate($templateName, $parameters, $options);
            
            $response = [
                'success' => true,
                'data' => [
                    'template_name' => $templateName,
                    'data' => $result['data'],
                    'pagination' => [
                        'total' => $result['total'],
                        'per_page' => $perPage,
                        'current_page' => $page,
                        'total_pages' => ceil($result['total'] / $perPage)
                    ]
                ]
            ];
            
            return $this->respond($response);
            
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
    
    /**
     * Get all reports
     * GET /api/reports
     */
    public function index()
    {
        $perPage = (int) ($this->request->getGet('per_page') ?: 20);
        $page = (int) ($this->request->getGet('page') ?: 1);
        
        $result = $this->reportModel->getReportsPaginated($perPage, $page);
        
        return $this->respond([
            'success' => true,
            'data' => $result['reports'],
            'pagination' => [
                'total' => $result['total'],
                'per_page' => $result['per_page'],
                'current_page' => $result['current_page'],
                'total_pages' => $result['total_pages']
            ]
        ]);
    }
    
    /**
     * Get report details
     * GET /api/reports/{id}
     */
    public function show($reportId)
    {
        try {
            $report = $this->definitionModel->getCompleteDefinition($reportId);
            
            if (!$report) {
                return $this->failNotFound('Report not found');
            }
            
            return $this->respond([
                'success' => true,
                'data' => $report
            ]);
            
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
 * Preview SQL
 * POST /api/reports/preview
 */
public function preview()
{
    try {
        $json = $this->request->getJSON(true);
        
        if (!$json) {
            return $this->failValidationError('Invalid JSON payload');
        }
        
        // Basic validation
        if (empty($json['report_name']) || empty($json['base_table'])) {
            return $this->failValidationError('Report name and base table are required');
        }
        
        // Build SQL from definition
        $sql = $this->buildSqlFromDefinition($json);
        
        return $this->respond([
            'success' => true,
            'sql' => $sql
        ]);
        
    } catch (\Exception $e) {
        return $this->failServerError($e->getMessage());
    }
}

/**
 * Build SQL from definition
 */
private function buildSqlFromDefinition(array $definition): string
{
    $select = $this->buildSelectFromDefinition($definition);
    $from = $definition['base_table'];
    $joins = $this->buildJoinsFromDefinition($definition);
    $where = $this->buildWhereFromDefinition($definition);
    
    $sql = "SELECT {$select} FROM {$from}";
    
    if ($joins) {
        $sql .= " {$joins}";
    }
    
    if ($where) {
        $sql .= " WHERE {$where}";
    }
    
    return $sql;
}

private function buildSelectFromDefinition(array $definition): string
{
    if (empty($definition['columns'])) {
        return '*';
    }
    
    $columns = [];
    foreach ($definition['columns'] as $column) {
        $expression = $column['column_expression'] ?? '';
        $alias = $column['alias'] ?? '';
        
        if ($expression && $alias) {
            $columns[] = "{$expression} AS `{$alias}`";
        } elseif ($expression) {
            $columns[] = $expression;
        }
    }
    
    return empty($columns) ? '*' : implode(', ', $columns);
}

private function buildJoinsFromDefinition(array $definition): string
{
    if (empty($definition['joins'])) {
        return '';
    }
    
    $joinClauses = [];
    foreach ($definition['joins'] as $join) {
        $type = strtoupper($join['join_type'] ?? 'INNER');
        $table = $join['table_name'] ?? '';
        $alias = $join['table_alias'] ?? '';
        $condition = $join['join_condition'] ?? '';
        
        if ($table && $condition) {
            $clause = "{$type} JOIN {$table}";
            if ($alias) {
                $clause .= " AS {$alias}";
            }
            $clause .= " ON {$condition}";
            $joinClauses[] = $clause;
        }
    }
    
    return implode(' ', $joinClauses);
}

private function buildWhereFromDefinition(array $definition): string
{
    if (empty($definition['filters'])) {
        return '';
    }
    
    $conditions = [];
    foreach ($definition['filters'] as $filter) {
        if (($filter['condition_type'] ?? '') === 'WHERE') {
            $expression = $filter['condition_expression'] ?? '';
            if ($expression) {
                $conditions[] = $expression;
            }
        }
    }
    
    return empty($conditions) ? '' : implode(' AND ', $conditions);
}
    
    /**
     * Create new report via API
     * POST /api/reports
     */
    public function create()
    {
        try {
            $json = $this->request->getJSON(true);
            
            if (!$json) {
                return $this->failValidationError('Invalid JSON payload');
            }
            
            $success = $this->definitionModel->saveCompleteDefinition($json);
            
            if ($success) {
                return $this->respondCreated([
                    'success' => true,
                    'message' => 'Report created successfully'
                ]);
            } else {
                return $this->fail('Failed to create report');
            }
            
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
    
    /**
     * Update report via API
     * PUT /api/reports/{id}
     */
    public function update($reportId)
    {
        try {
            $json = $this->request->getJSON(true);
            
            if (!$json) {
                return $this->failValidationError('Invalid JSON payload');
            }
            
            $json['id'] = $reportId;
            $success = $this->definitionModel->saveCompleteDefinition($json);
            
            if ($success) {
                return $this->respondUpdated([
                    'success' => true,
                    'message' => 'Report updated successfully'
                ]);
            } else {
                return $this->fail('Failed to update report');
            }
            
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
    
    /**
     * Delete report via API
     * DELETE /api/reports/{id}
     */
    public function delete($reportId)
    {
        try {
            $this->reportModel->delete($reportId);
            
            return $this->respondDeleted([
                'success' => true,
                'message' => 'Report deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
    
    /**
     * Export report to CSV via API
     * GET /api/reports/{id}/export/csv
     */
    public function exportCsv($reportId)
    {
        try {
            $parameters = $this->request->getGet();
            $filename = $this->request->getGet('filename') ?: 'report_' . $reportId . '_' . date('Y-m-d') . '.csv';
            
            $response = $this->reportBuilderService->exportToCsv($reportId, $parameters, $filename);
            
            return $response;
            
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
    
    /**
     * Export report to Excel via API
     * GET /api/reports/{id}/export/excel
     */
    public function exportExcel($reportId)
    {
        try {
            $parameters = $this->request->getGet();
            $filename = $this->request->getGet('filename') ?: 'report_' . $reportId . '_' . date('Y-m-d') . '.xlsx';
            
            $response = $this->reportBuilderService->exportToExcel($reportId, $parameters, $filename);
            
            return $response;
            
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
    
    /**
     * Get report parameters
     * GET /api/reports/{id}/parameters
     */
    public function getParameters($reportId)
    {
        try {
            $parameters = $this->reportBuilderService->getReportParameters($reportId);
            
            return $this->respond([
                'success' => true,
                'data' => $parameters
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
    
    /**
     * Get database tables
     * GET /api/database/tables
     */
    public function getDatabaseTables()
    {
        try {
            $db = db_connect();
            $tables = $db->listTables();
            
            return $this->respond([
                'success' => true,
                'data' => $tables
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
    
    /**
     * Get table columns
     * GET /api/database/tables/{table}/columns
     */
    public function getTableColumns($table)
    {
        try {
            $db = db_connect();
            
            if (!$db->tableExists($table)) {
                return $this->failNotFound("Table '{$table}' not found");
            }
            
            $fields = $db->getFieldData($table);
            $columns = array_map(function($field) {
                return [
                    'name' => $field->name,
                    'type' => $field->type,
                    'max_length' => $field->max_length,
                    'primary_key' => $field->primary_key ?? 0
                ];
            }, $fields);
            
            return $this->respond([
                'success' => true,
                'data' => $columns
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
    
    /**
     * Test SQL expression
     * POST /api/sql/test-expression
     */
    public function testExpression()
    {
        try {
            $json = $this->request->getJSON(true);
            
            if (!isset($json['expression'])) {
                return $this->failValidationError('Expression is required');
            }
            
            $sqlExpressionService = new \App\Services\SqlExpressionService(db_connect());
            $isValid = $sqlExpressionService->validateExpression($json['expression']);
            
            return $this->respond([
                'success' => true,
                'data' => [
                    'is_valid' => $isValid,
                    'expression' => $json['expression']
                ]
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
    
    /**
     * Create template from report
     * POST /api/reports/{id}/templates
     */
    public function createTemplate($reportId)
    {
        try {
            $json = $this->request->getJSON(true);
            
            if (!isset($json['template_name'])) {
                return $this->failValidationError('Template name is required');
            }
            
            $description = $json['description'] ?? null;
            $success = $this->definitionModel->createTemplate($reportId, $json['template_name'], $description);
            
            if ($success) {
                return $this->respondCreated([
                    'success' => true,
                    'message' => 'Template created successfully'
                ]);
            } else {
                return $this->fail('Failed to create template');
            }
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
    
    /**
     * Get all templates
     * GET /api/reports/templates
     */
    public function getTemplates()
    {
        try {
            $templates = $this->definitionModel->getTemplates();
            
            return $this->respond([
                'success' => true,
                'data' => $templates
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}