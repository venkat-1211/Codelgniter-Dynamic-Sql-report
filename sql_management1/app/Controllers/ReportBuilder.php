<?php
// app/Controllers/ReportBuilder.php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\AdvancedQueryBuilder;
use App\Libraries\ReportExporter;
use App\Models\ReportModel;

class ReportBuilder extends BaseController
{
    protected $reportModel;
    protected $queryBuilder;
    protected $exporter;
    protected $perPage = 50;

    public function __construct()
    {
        $this->reportModel = new ReportModel();
        $this->queryBuilder = new AdvancedQueryBuilder();
        $this->exporter = new ReportExporter();
        
        helper(['form', 'url', 'text']);
    }

    public function index(): string
    {
        $role = session()->get('role') ?? 'user';
        $data = [
            'title' => 'Advanced Report Builder',
            'config' => config('ReportSettings'),
            'reports' => $this->reportModel->getReportsForRole($role),
            'user_role' => $role
        ];
        
        return view('report_builder/dashboard', $data);
    }

    public function create()
    {
        if ($this->request->getMethod() === 'post') {
            return $this->saveReport();
        }
        
        $data = [
            'title' => 'Create New Report',
            'config' => config('ReportSettings'),
            'action' => 'create',
            'report_templates' => $this->getReportTemplates()
        ];
        
        return view('report_builder/create', $data);
    }

    public function edit($id = null)
    {
        $report = $this->reportModel->find($id);
        
        if (!$report) {
            return redirect()->to('/reports')->with('error', 'Report not found!');
        }
        
        if ($this->request->getMethod() === 'post') {
            return $this->saveReport($id);
        }
        
        $data = [
            'title' => 'Edit Report',
            'config' => config('ReportSettings'),
            'report' => $report,
            'action' => 'edit',
            'report_templates' => $this->getReportTemplates()
        ];
        
        return view('report_builder/edit', $data);
    }

    protected function saveReport($id = null)
    {
        $validation = \Config\Services::validation();
        $validation->setRules([
            'report_name' => 'required|min_length[3]|max_length[255]',
            'report_type' => 'required|in_list[simple,advanced,custom_sql]'
        ]);
        
        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }
        
        $userId = session()->get('id') ?? 1;
        $reportType = $this->request->getPost('report_type');
        
        // Prepare report data based on type
        $reportData = [
            'report_name' => $this->request->getPost('report_name'),
            'description' => $this->request->getPost('description'),
            'report_type' => $reportType,
            'access_roles' => json_encode($this->request->getPost('access_roles') ?? []),
            'export_formats' => json_encode($this->request->getPost('export_formats') ?? ['xlsx', 'csv']),
            'created_by' => $userId,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Handle different report types
        switch ($reportType) {
            case 'custom_sql':
                $reportData['custom_sql'] = $this->request->getPost('custom_sql');
                break;
                
            case 'advanced':
                $reportData['base_tables'] = json_encode($this->request->getPost('base_tables') ?? []);
                $reportData['joins_config'] = json_encode($this->request->getPost('joins') ?? []);
                $reportData['columns_config'] = json_encode($this->request->getPost('columns') ?? []);
                $reportData['calculated_fields'] = json_encode($this->request->getPost('calculated_fields') ?? []);
                $reportData['filters_config'] = json_encode($this->request->getPost('filters') ?? []);
                $reportData['grouping_config'] = json_encode($this->request->getPost('grouping') ?? []);
                $reportData['sorting_config'] = json_encode($this->request->getPost('sorting') ?? []);
                $reportData['subqueries_config'] = json_encode($this->request->getPost('subqueries') ?? []);
                break;
                
            default: // simple
                $reportData['base_table'] = $this->request->getPost('base_table');
                $reportData['columns_config'] = json_encode($this->request->getPost('columns') ?? []);
                $reportData['filters_config'] = json_encode($this->request->getPost('filters') ?? []);
                $reportData['grouping_config'] = json_encode($this->request->getPost('grouping') ?? []);
                $reportData['sorting_config'] = json_encode($this->request->getPost('sorting') ?? []);
        }
        
        if ($id) {
            // Update
            unset($reportData['created_at']);
            if ($this->reportModel->update($id, $reportData)) {
                return redirect()->to('/reports')->with('success', 'Report updated successfully!');
            }
        } else {
            // Create
            if ($this->reportModel->insert($reportData)) {
                return redirect()->to('/reports')->with('success', 'Report created successfully!');
            }
        }
        
        return redirect()->back()->with('error', 'Failed to save report');
    }

    public function preview($id)
    {
        $report = $this->reportModel->find($id);
        
        if (!$report) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Report not found'
            ])->setStatusCode(404);
        }
        
        try {
            $parameters = $this->request->getGet();
            $page = (int) ($parameters['page'] ?? 1);
            $perPage = (int) ($parameters['per_page'] ?? $this->perPage);
            
            // Prepare config based on report type
            $config = $this->prepareReportConfig($report, $parameters);
            
            $result = $this->queryBuilder->executeWithPagination($config, $page, $perPage);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $result['data'],
                'pagination' => [
                    'total' => $result['total'],
                    'per_page' => $result['per_page'],
                    'current_page' => $result['current_page'],
                    'total_pages' => $result['total_pages']
                ],
                'execution_time' => number_format($result['execution_time'], 3) . 's',
                'sql' => $result['sql'] ?? null
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ])->setStatusCode(500);
        }
    }

    public function export($id)
    {
        $report = $this->reportModel->find($id);
        
        if (!$report) {
            return redirect()->to('/reports')->with('error', 'Report not found!');
        }
        
        $format = $this->request->getGet('format') ?? 'xlsx';
        
        if (!$report->canExport($format)) {
            return redirect()->back()->with('error', 'Export format not allowed!');
        }
        
        try {
            $parameters = $this->request->getGet();
            
            // Prepare config
            $config = $this->prepareReportConfig($report, $parameters);
            
            // Get all data (no pagination for export)
            $sql = $this->queryBuilder->buildQuery($config);
            $db = db_connect();
            $data = $db->query($sql, $this->queryBuilder->getParams())->getResultArray();
            
            if (empty($data)) {
                return redirect()->back()->with('error', 'No data to export!');
            }
            
            // Prepare columns for export
            $columns = $this->prepareExportColumns($report, $config);
            
            // Export based on format
            switch ($format) {
                case 'xlsx':
                    $filename = $this->exporter->exportToExcel($data, $columns, $report->report_name);
                    break;
                case 'csv':
                    $filename = $this->exporter->exportToCSV($data, $columns, $report->report_name);
                    break;
                default:
                    throw new \InvalidArgumentException("Unsupported format: {$format}");
            }
            
            // Log execution
            $userId = session()->get('id') ?? 1;
            $this->reportModel->logExecution($id, $parameters, $userId, [
                'record_count' => count($data),
                'export_format' => $format,
                'file_path' => $filename,
                'status' => 'success'
            ]);
            
            // Get file info
            $fileInfo = $this->exporter->getFileInfo($filename);
            
            // Send file for download
            return $this->response->download($filename, null)
                ->setFileName($this->generateFilename($report->report_name, $format))
                ->setContentType($fileInfo['mime']);
            
        } catch (\Exception $e) {
            // Log error
            $userId = session()->get('id') ?? 1;
            $this->reportModel->logExecution($id, $parameters ?? [], $userId, [
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Prepare report configuration
     */
    protected function prepareReportConfig($report, $parameters): array
    {
        $config = [
            'report_type' => $report->report_type
        ];
        
        switch ($report->report_type) {
            case 'custom_sql':
                $config['custom_sql'] = $report->custom_sql;
                $config['parameters'] = $parameters;
                break;
                
            case 'advanced':
                $config['base_tables'] = $report->getBaseTables();
                $config['joins'] = $this->applyParameters($report->getJoinsConfig(), $parameters);
                $config['columns'] = $this->applyParameters($report->getColumnsConfig(), $parameters);
                $config['calculated_fields'] = $this->applyParameters($report->getCalculatedFields(), $parameters);
                $config['filters'] = $this->applyParameters($report->getFiltersConfig(), $parameters);
                $config['grouping'] = $this->applyParameters($report->getGroupingConfig(), $parameters);
                $config['sorting'] = $this->applyParameters($report->getSortingConfig(), $parameters);
                $config['subqueries'] = $this->applyParameters($report->getSubqueriesConfig(), $parameters);
                break;
                
            default:
                $config['base_table'] = $report->base_table;
                $config['columns'] = $this->applyParameters($report->getColumnsConfig(), $parameters);
                $config['filters'] = $this->applyParameters($report->getFiltersConfig(), $parameters);
                $config['grouping'] = $this->applyParameters($report->getGroupingConfig(), $parameters);
                $config['sorting'] = $this->applyParameters($report->getSortingConfig(), $parameters);
        }
        
        return $config;
    }

    /**
     * Apply parameters to configurations
     */
    protected function applyParameters(array $items, array $parameters): array
    {
        foreach ($items as &$item) {
            if (isset($item['parameter']) && isset($parameters[$item['parameter']])) {
                $item['value'] = $parameters[$item['parameter']];
            }
        }
        
        return $items;
    }

    /**
     * Prepare columns for export
     */
    protected function prepareExportColumns($report, $config): array
    {
        $columns = [];
        
        if ($report->report_type === 'custom_sql') {
            // For custom SQL, use the first row keys as column headers
            if (!empty($config['data'][0])) {
                foreach (array_keys($config['data'][0]) as $key) {
                    $columns[] = [
                        'field' => $key,
                        'label' => ucwords(str_replace('_', ' ', $key))
                    ];
                }
            }
        } else {
            // For structured reports, use configured columns
            foreach ($config['columns'] as $column) {
                $columns[] = [
                    'field' => $column['field'],
                    'label' => $column['alias'] ?? ucwords(str_replace('_', ' ', $column['field']))
                ];
            }
            
            // Add calculated fields
            if (!empty($config['calculated_fields'])) {
                foreach ($config['calculated_fields'] as $field) {
                    $columns[] = [
                        'field' => $field['alias'],
                        'label' => $field['alias']
                    ];
                }
            }
        }
        
        return $columns;
    }

    /**
     * Get report templates
     */
    protected function getReportTemplates(): array
    {
        $config = config('ReportSettings');
        return $config->reportTemplates ?? [];
    }

    /**
     * Test SQL query
     */
    public function testSql()
    {
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setJSON(['success' => false, 'error' => 'Method not allowed']);
        }
        
        $sql = $this->request->getPost('sql');
        $parameters = $this->request->getPost('parameters') ?? [];
        
        if (empty($sql)) {
            return $this->response->setJSON(['success' => false, 'error' => 'SQL is required']);
        }
        
        $result = $this->queryBuilder->testQuery($sql, $parameters);
        
        return $this->response->setJSON($result);
    }

    /**
     * Load template
     */
    public function loadTemplate($templateName)
    {
        $config = config('ReportSettings');
        
        if (!isset($config->reportTemplates[$templateName])) {
            return $this->response->setJSON(['success' => false, 'error' => 'Template not found']);
        }
        
        $template = $config->reportTemplates[$templateName];
        
        return $this->response->setJSON([
            'success' => true,
            'template' => $template
        ]);
    }

    /**
     * Generate filename for export
     */
    protected function generateFilename(string $reportName, string $format): string
    {
        $safeName = preg_replace('/[^A-Za-z0-9\-]/', '_', $reportName);
        $date = date('Y-m-d_H-i-s');
        return "{$safeName}_{$date}.{$format}";
    }

    public function metadata()
    {
        $config = config('ReportSettings');
        
        return $this->response->setJSON([
            'success' => true,
            'tables' => $config->availableTables,
            'join_types' => $config->joinTypes,
            'complex_functions' => $config->complexFunctions,
            'templates' => $config->reportTemplates
        ]);
    }

    public function delete($id)
    {
        $report = $this->reportModel->find($id);
        
        if (!$report) {
            return redirect()->to('/reports')->with('error', 'Report not found!');
        }
        
        if ($this->reportModel->delete($id)) {
            return redirect()->to('/reports')->with('success', 'Report deleted successfully!');
        }
        
        return redirect()->back()->with('error', 'Failed to delete report');
    }
}