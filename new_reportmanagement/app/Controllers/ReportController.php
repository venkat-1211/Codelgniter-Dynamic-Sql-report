<?php

namespace App\Controllers;

use App\Models\ReportModel;
use App\Services\ReportBuilderService;
use CodeIgniter\Exceptions\PageNotFoundException;

class ReportController extends BaseController
{
    protected $reportModel;
    protected $reportBuilderService;
    protected $validation;
    
    public function __construct()
    {
        $this->reportModel = new ReportModel();
        $this->reportBuilderService = new ReportBuilderService();
        $this->validation = \Config\Services::validation();
        
        // Set common validation rules
        $this->validation->setRules([
            'report_name' => 'required|min_length[3]|max_length[255]',
            'base_table' => 'required|min_length[1]|max_length[255]',
        ]);
        
        helper('form');
    }
    
    /**
     * List all reports
     */
    public function index()
    {
        $perPage = 20;
        $page = $this->request->getGet('page') ?: 1;
        
        $data = $this->reportModel->getReportsPaginated($perPage, $page);
        $data['title'] = 'Reports';
        $data['stats'] = $this->reportModel->getReportStats();
        $data['tables'] = $this->getDatabaseTables(); // Add this line
        
        return view('reports/index', $data);
    }
    
    /**
     * View report result
     */
    public function view($reportId)
    {
        $perPage = $this->request->getGet('per_page') ?: 50;
        $page = $this->request->getGet('page') ?: 1;
        $export = $this->request->getGet('export');
        
        // Get report details
        $definitionModel = new \App\Models\ReportDefinitionModel();
        $report = $definitionModel->find($reportId);
        
        if (!$report) {
            throw new PageNotFoundException("Report not found");
        }
        
        // Get parameters from request
        $parameters = $this->request->getGet();
        unset($parameters['per_page'], $parameters['page'], $parameters['export']);
        
        // Generate report
        $options = [
            'limit' => $export ? null : $perPage,
            'offset' => $export ? null : ($page - 1) * $perPage,
            'debug' => $this->request->getGet('debug') === '1'
        ];
        
        try {
            $result = $this->reportBuilderService->generateReport($reportId, $parameters, $options);
            
            // Handle export
            if ($export === 'csv') {
                return $this->reportBuilderService->exportToCsv($reportId, $parameters);
            } elseif ($export === 'excel') {
                return $this->reportBuilderService->exportToExcel($reportId, $parameters);
            }
            
            // Prepare pagination
            $pager = service('pager');
            $pager->makeLinks($page, $perPage, $result['total']);
            
            $data = [
                'title' => $report['report_name'],
                'report' => $report,
                'data' => $result['data'],
                'total' => $result['total'],
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => ceil($result['total'] / $perPage),
                'parameters' => $parameters,
                'sql' => $result['sql'] ?? null,
                'definition' => $result['definition']
            ];
            
            return view('reports/result', $data);
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }
    
    /**
     * Run report from template
     */
    public function template($templateName)
    {
        $parameters = $this->request->getGet();
        $export = $this->request->getGet('export');
        
        try {
            $result = $this->reportBuilderService->generateFromTemplate($templateName, $parameters);
            
            // Handle export
            if ($export === 'csv') {
                return $this->reportBuilderService->exportToCsv(
                    $result['definition']['id'] ?? 0, 
                    $parameters,
                    $templateName . '_' . date('Y-m-d') . '.csv'
                );
            }
            
            $data = [
                'title' => "Template: {$templateName}",
                'data' => $result['data'],
                'total' => $result['total'],
                'template_name' => $templateName,
                'parameters' => $parameters,
                'sql' => $result['sql'] ?? null
            ];
            
            return view('reports/result', $data);
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }
    
    /**
     * Create new report
     */
    public function create()
    {
        if ($this->request->getMethod() === 'POST') {
            $definitionModel = new \App\Models\ReportDefinitionModel();
            
            $postData = $this->request->getPost();
            
            try {
                // Basic validation
                if (!$this->validation->run($postData)) {
                    throw new \RuntimeException(implode(', ', $this->validation->getErrors()));
                }
                
                // Save report definition
                $success = $definitionModel->saveCompleteDefinition($postData);
                
                if ($success) {
                    return redirect()->to('/reports')
                        ->with('success', 'Report created successfully');
                } else {
                    throw new \RuntimeException('Failed to save report');
                }
                
            } catch (\Exception $e) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', $e->getMessage());
            }
        }
        
        $data = [
            'title' => 'Create New Report',
            'tables' => $this->getDatabaseTables()
        ];
        
        return view('reports/builder', $data);
    }
    
    /**
     * Edit existing report
     */
    public function edit($reportId)
    {
        $definitionModel = new \App\Models\ReportDefinitionModel();
        $report = $definitionModel->getCompleteDefinition($reportId);
        
        if (!$report) {
            throw new PageNotFoundException("Report not found");
        }
        
        if ($this->request->getMethod() === 'POST') {
            $postData = $this->request->getPost();
            $postData['id'] = $reportId;
            
            try {
                // Basic validation
                if (!$this->validation->run($postData)) {
                    throw new \RuntimeException(implode(', ', $this->validation->getErrors()));
                }
                
                // Save report definition
                $success = $definitionModel->saveCompleteDefinition($postData);
                
                if ($success) {
                    return redirect()->to("/reports/view/{$reportId}")
                        ->with('success', 'Report updated successfully');
                } else {
                    throw new \RuntimeException('Failed to update report');
                }
                
            } catch (\Exception $e) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', $e->getMessage());
            }
        }
        
        $data = [
            'title' => 'Edit Report: ' . $report['report_name'],
            'report' => $report,
            'tables' => $this->getDatabaseTables(),
            'edit_mode' => true
        ];
        
        return view('reports/builder', $data);
    }
    
    /**
     * Delete report
     */
    public function delete($reportId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(405)->setBody('Method not allowed');
        }
        
        try {
            $this->reportModel->delete($reportId);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Report deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Clone report
     */
    public function clone($reportId)
    {
        $newName = $this->request->getPost('new_name');
        
        if (!$newName) {
            return redirect()->back()
                ->with('error', 'New report name is required');
        }
        
        try {
            $success = $this->reportModel->cloneReport($reportId, $newName);
            
            if ($success) {
                return redirect()->to('/reports')
                    ->with('success', 'Report cloned successfully');
            } else {
                throw new \RuntimeException('Failed to clone report');
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
    
    /**
     * Export report definition
     */
    public function export($reportId)
    {
        try {
            $json = $this->reportModel->exportDefinition($reportId);
            
            $report = $this->reportModel->find($reportId);
            $filename = str_replace(' ', '_', strtolower($report['report_name'])) . '_definition.json';
            
            return $this->response->download($filename, $json);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
    
    /**
     * Import report definition
     */
    public function import()
    {
        if ($this->request->getMethod() === 'POST') {
            $jsonFile = $this->request->getFile('definition_file');
            $reportName = $this->request->getPost('report_name');
            
            if (!$jsonFile->isValid()) {
                return redirect()->back()
                    ->with('error', 'Invalid file upload');
            }
            
            if ($jsonFile->getExtension() !== 'json') {
                return redirect()->back()
                    ->with('error', 'Only JSON files are allowed');
            }
            
            try {
                $jsonContent = file_get_contents($jsonFile->getTempName());
                $success = $this->reportModel->importDefinition($jsonContent, $reportName);
                
                if ($success) {
                    return redirect()->to('/reports')
                        ->with('success', 'Report imported successfully');
                } else {
                    throw new \RuntimeException('Failed to import report');
                }
            } catch (\Exception $e) {
                return redirect()->back()
                    ->with('error', $e->getMessage());
            }
        }
        
        $data = [
            'title' => 'Import Report'
        ];
        
        return view('reports/import', $data);
    }
    
    /**
     * Get database tables for dropdown
     */
    private function getDatabaseTables(): array
    {
        $db = db_connect();
        return $db->listTables();
    }
    
    /**
     * Get table columns for autocomplete
     */
    public function getTableColumns()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(405)->setBody('Method not allowed');
        }
        
        $table = $this->request->getGet('table');
        
        if (!$table) {
            return $this->response->setJSON([]);
        }
        
        $db = db_connect();
        
        try {
            $fields = $db->getFieldData($table);
            $columns = array_map(function($field) {
                return $field->name;
            }, $fields);
            
            return $this->response->setJSON($columns);
        } catch (\Exception $e) {
            return $this->response->setJSON([]);
        }
    }
    
    /**
     * Search reports
     */
    public function search()
    {
        $keyword = $this->request->getGet('q');
        
        if (!$keyword) {
            return $this->response->setJSON([]);
        }
        
        $reports = $this->reportModel->searchReports($keyword, 10);
        
        return $this->response->setJSON($reports);
    }
}