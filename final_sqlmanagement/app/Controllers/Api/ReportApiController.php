<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ReportModel;
use App\Services\ReportBuilderService;
use CodeIgniter\API\ResponseTrait;

class ReportApiController extends BaseController
{
    use ResponseTrait;
    
    protected $reportModel;
    protected $reportService;
    
    public function __construct()
    {
        $this->reportModel = new ReportModel();
        $this->reportService = new ReportBuilderService();
        
        $this->helpers = ['form', 'url'];
    }
    
    /**
     * Get all reports (API)
     */
    public function index()
    {
        $page = $this->request->getGet('page') ?? 1;
        $perPage = $this->request->getGet('per_page') ?? 20;
        $search = $this->request->getGet('search') ?? '';
        
        $data = $this->reportModel->getAllReports($page, $perPage, $search);
        
        return $this->respond([
            'status' => 'success',
            'data' => $data['data'],
            'pagination' => [
                'total' => $data['total'],
                'page' => $data['page'],
                'perPage' => $data['perPage'],
                'totalPages' => $data['totalPages']
            ]
        ]);
    }
    
    /**
     * Execute report (API)
     */
    public function execute($id)
    {
        $parameters = $this->request->getGet();
        $limit = $this->request->getGet('limit') ?? 100;
        $offset = $this->request->getGet('offset') ?? 0;
        
        try {
            $result = $this->reportService->executeDynamicReport($id, $parameters, [
                'limit' => $limit,
                'offset' => $offset
            ]);
            
            return $this->respond([
                'status' => 'success',
                'data' => $result['data'],
                'total' => $result['total'],
                'parameters' => $parameters
            ]);
            
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
    
    /**
     * Export to CSV (API)
     */
    public function exportCsv($id)
    {
        $parameters = $this->request->getGet();
        
        try {
            $result = $this->reportService->generateCSV($id, $parameters);
            
            return $this->respond([
                'status' => 'success',
                'filename' => $result['filename'],
                'content' => $result['content'],
                'count' => $result['count']
            ]);
            
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
    
    /**
     * Export to Excel (API)
     */
    public function exportExcel($id)
    {
        $parameters = $this->request->getGet();
        
        try {
            $result = $this->reportService->generateExcel($id, $parameters);
            
            // Read file content
            $content = file_get_contents($result['path']);
            
            // Clean up temp file
            unlink($result['path']);
            
            return $this->response
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $result['filename'] . '"')
                ->setBody($content);
            
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
    
    /**
     * Get report details (API)
     */
    public function show($id)
    {
        try {
            $report = $this->reportModel->getReportWithDetails($id);
            
            if (!$report) {
                return $this->failNotFound('Report not found');
            }
            
            return $this->respond([
                'status' => 'success',
                'data' => $report
            ]);
            
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
    
    /**
     * Create new report (API)
     */
    public function create()
    {
        $data = $this->request->getJSON(true);
        
        try {
            $model = new \App\Models\ReportDefinitionModel();
            $reportId = $model->saveReportDefinition($data);
            
            return $this->respondCreated([
                'status' => 'success',
                'message' => 'Report created successfully',
                'report_id' => $reportId
            ]);
            
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
    
    /**
     * Update report (API)
     */
    public function update($id)
    {
        $data = $this->request->getJSON(true);
        $data['id'] = $id;
        
        try {
            $model = new \App\Models\ReportDefinitionModel();
            $reportId = $model->saveReportDefinition($data);
            
            return $this->respondUpdated([
                'status' => 'success',
                'message' => 'Report updated successfully',
                'report_id' => $reportId
            ]);
            
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
    
    /**
     * Delete report (API)
     */
    public function delete($id)
    {
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
     * Validate report parameters (API)
     */
    public function validate($id)
    {
        $parameters = $this->request->getJSON(true);
        
        try {
            $report = $this->reportModel->getReportWithDetails($id);
            
            if (!$report) {
                return $this->failNotFound('Report not found');
            }
            
            $errors = $this->reportService->validateParameters($parameters, $report);
            
            return $this->respond([
                'status' => empty($errors) ? 'success' : 'error',
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
    
    /**
     * Get available database tables (API)
     */
    public function tables()
    {
        try {
            $model = new \App\Models\ReportDefinitionModel();
            $tables = $model->getDatabaseTables();
            
            return $this->respond([
                'status' => 'success',
                'tables' => $tables
            ]);
            
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
    
    /**
     * Get table columns (API)
     */
    public function columns($table)
    {
        try {
            $model = new \App\Models\ReportDefinitionModel();
            $columns = $model->getTableColumns($table);
            
            return $this->respond([
                'status' => 'success',
                'columns' => $columns
            ]);
            
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}