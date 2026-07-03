<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ReportModel;
use App\Models\ReportDefinitionModel;
use App\Services\ReportBuilderService;
use CodeIgniter\API\ResponseTrait;

class ReportController extends BaseController
{
    use ResponseTrait;
    
    protected $reportModel;
    protected $reportDefinitionModel;
    protected $reportService;
    
    public function __construct()
    {
        $this->reportModel = new ReportModel();
        $this->reportDefinitionModel = new ReportDefinitionModel();
        $this->reportService = new ReportBuilderService();
        
        helper(['form', 'url']);
    }
    
    /**
     * Display list of reports
     */
    public function index()
    {
        $page = $this->request->getGet('page') ?? 1;
        $perPage = $this->request->getGet('per_page') ?? 20;
        $search = $this->request->getGet('search') ?? '';
        
        $data = $this->reportModel->getAllReports($page, $perPage, $search);
        
        return view('reports/index', $data);
    }
    
    /**
     * Show report builder UI
     */
    public function builder($id = null)
    {
        $data = [];
        
        if ($id) {
            $data['report'] = $this->reportModel->getReportWithDetails($id);
        }
        
        // Get database tables for UI
        $data['tables'] = $this->reportDefinitionModel->getDatabaseTables();
        
        return view('reports/builder', $data);
    }
    
    /**
     * Save report definition
     */
    public function save()
    {
        if (!$this->request->isAJAX()) {
            return $this->failForbidden('Access denied');
        }
        
        $postData = $this->request->getPost();
        
        try {
            $reportId = $this->reportDefinitionModel->saveReportDefinition($postData);
            
            return $this->respondCreated([
                'status' => 'success',
                'message' => 'Report saved successfully',
                'report_id' => $reportId
            ]);
            
        } catch (\Exception $e) {
            log_message(
                'error',
                'Preview SQL Error: ' . $e->getMessage() .
                ' at line ' . $e->getLine() .
                ' in file ' . $e->getFile()
            );
            return $this->fail($e->getMessage());
        }
    }
    
    /**
     * Execute and display report
     */
    public function execute($id)
    {
        $parameters = $this->request->getGet();
        $page = $this->request->getGet('page') ?? 1;
        $perPage = $this->request->getGet('per_page') ?? 100;
        
        try {
            $offset = ($page - 1) * $perPage;
            
            $result = $this->reportService->executeDynamicReport($id, $parameters);
            
            $report = $this->reportModel->find($id);
            
            $data = [
                'report' => $report,
                'data' => $result['data'],
                'total' => $result['total'],
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($result['total'] / $perPage),
                'parameters' => $parameters,
                'sql' => $result['sql'] // For debugging
            ];
            
            return view('reports/result', $data);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
    
    /**
     * Export report to CSV
     */
    public function exportCSV($id)
    {
        $parameters = $this->request->getGet();
        
        try {
            $result = $this->reportService->generateCSV($id, $parameters);
            
            $report = $this->reportModel->find($id);
            $filename = ($report ? str_replace(' ', '_', $report['report_name']) : 'report') . '.csv';
            
            return $this->response->download($filename, $result['content']);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
    
    /**
     * Export report to Excel
     */
    // public function exportExcel($id)
    // {
    //     $parameters = $this->request->getGet();
        
    //     try {
    //         $result = $this->reportService->generateExcel($id, $parameters);
    //         return $this->response->download($result['path'], null, true)->setFileName($result['filename']);
            
    //     } catch (\Exception $e) {
    //         log_message(
    //             'error',
    //             'Preview SQL Error: ' . $e->getMessage() .
    //             ' at line ' . $e->getLine() .
    //             ' in file ' . $e->getFile()
    //         );
    //         return redirect()->back()->with('error', $e->getMessage());
    //     }
    // }

    public function exportExcel($id)
    {
        $parameters = $this->request->getGet();
        
        try {
            $result = $this->reportService->generateExcel($id, $parameters);
            
            // Check if file exists
            if (!file_exists($result['path'])) {
                throw new \Exception('Excel file not found: ' . $result['path']);
            }
            
            // Get file size
            $fileSize = filesize($result['path']);
            
            // Set headers and send file
            return $this->response->download($result['path'], null)
                ->setFileName($result['filename'])
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Length', $fileSize)
                ->setHeader('Content-Disposition', 'attachment; filename="' . $result['filename'] . '"')
                ->setHeader('Cache-Control', 'max-age=0')
                ->setHeader('Expires', '0')
                ->setHeader('Pragma', 'public');
                
        } catch (\Exception $e) {
            log_message('error', 'Excel Export Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete report
     */
    public function delete()
    {
        if (!$this->request->isAJAX()) {
            return $this->failForbidden('Access denied');
        }

        $id = $this->request->getVar('id');
        try {
            $this->reportModel->delete($id);
            
            return $this->respondDeleted([
                'status' => 'success',
                'message' => 'Report deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
    
    /**
     * Clone report
     */
    public function clone($id)
    {
        try {
            $report = $this->reportModel->getReportWithDetails($id);
            
            if (!$report) {
                throw new \Exception('Report not found');
            }
            
            // Remove ID and change name
            unset($report['id']);
            $report['report_name'] = $report['report_name'] . ' (Copy)';
            $report['is_template'] = 0;
            
            $newId = $this->reportDefinitionModel->saveReportDefinition($report);
            
            return redirect()->to("/reports/builder/{$newId}")->with('success', 'Report cloned successfully');
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
    
    /**
     * Get table columns for UI
     */
    public function getTableColumns()
    {
        if (!$this->request->isAJAX()) {
            return $this->failForbidden('Access denied');
        }
        
        $tableName = $this->request->getGet('table');
        
        try {
            $columns = $this->reportDefinitionModel->getTableColumns($tableName);
            
            return $this->respond([
                'status' => 'success',
                'columns' => $columns
            ]);
            
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
    
    /**
     * Preview SQL
     */
    // public function previewSql()
    // {
    //     if (!$this->request->isAJAX()) {
    //         return $this->failForbidden('Access denied');
    //     }
        
    //     $postData = $this->request->getPost();
    //     try {
    //         // Create temporary report object
    //         $report = [
    //             'base_table' => $postData['base_table'],
    //             'columns' => $postData['columns'] ?? [],
    //             'joins' => $postData['joins'] ?? [],
    //             'conditions' => $postData['conditions'] ?? [],
    //             'groups' => $postData['groups'] ?? [],
    //             'orders' => $postData['orders'] ?? []
    //         ];
            
    //         $queryBuilder = new \App\Libraries\DynamicQueryBuilder();
    //         $result = $queryBuilder->buildQuery($report);
            
    //         return $this->respond([
    //             'status' => 'success',
    //             'sql' => $result['sql']
    //         ]);
            
    //     } catch (\Exception $e) {
    //         return $this->fail($e->getMessage() . $e->getLine());
    //     }
    // }
    public function previewSql()
    {
        if (!$this->request->isAJAX()) {
            return $this->failForbidden('Access denied');
        }
        
        try {
            // Get JSON data instead of POST data
            $postData = $this->request->getJSON(true);
            
            if (!$postData) {
                throw new \Exception('No data received');
            }
            
            // Debug logging (remove in production)
            // log_message('debug', 'Preview SQL Data: ' . print_r($postData, true));
            
            // Create temporary report object
            $report = [
                'base_table' => $postData['base_table'] ?? '',
                'columns' => $postData['columns'] ?? [],
                'joins' => $postData['joins'] ?? [],
                'conditions' => $postData['conditions'] ?? [],
                'groups' => $postData['groups'] ?? [],
                'orders' => $postData['orders'] ?? []
            ];
            
            // Validate required fields
            if (empty($report['base_table'])) {
                throw new \Exception('Base table is required');
            }
            
            $queryBuilder = new \App\Libraries\DynamicQueryBuilder();
            $result = $queryBuilder->buildQuery($report);
            
            return $this->respond([
                'status' => 'success',
                'sql' => $result['sql']
            ]);
            
        } catch (\Exception $e) {
            // Log the error for debugging
            log_message(
                'error',
                'Preview SQL Error: ' . $e->getMessage() .
                ' at line ' . $e->getLine() .
                ' in file ' . $e->getFile()
            );
            
            return $this->fail($e->getMessage());
        }
    }
}