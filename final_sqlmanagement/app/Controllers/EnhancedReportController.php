<?php

namespace App\Controllers;

use App\Services\EnhancedReportBuilderService;

class EnhancedReportController extends ReportController
{
    protected $enhancedReportService;
    
    public function __construct()
    {
        parent::__construct();
        $this->enhancedReportService = new EnhancedReportBuilderService();
    }
    
    /**
     * Execute complex report with advanced features
     */
    public function executeComplex($id)
    {
        $parameters = $this->request->getGet();
        $page = $this->request->getGet('page') ?? 1;
        $perPage = $this->request->getGet('per_page') ?? 100;
        
        try {
            $offset = ($page - 1) * $perPage;
            
            $result = $this->enhancedReportService->executeComplexReport($id, $parameters, [
                'limit' => $perPage,
                'offset' => $offset
            ]);
            
            $report = $this->reportModel->find($id);
            
            $data = [
                'report' => $report,
                'data' => $result['data'],
                'total' => $result['total'],
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($result['total'] / $perPage),
                'parameters' => $parameters,
                'sql' => $result['sql'],
                'is_complex' => true
            ];
            
            return view('reports/complex_result', $data);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
    
    /**
     * Save enhanced report definition
     */
    public function saveEnhanced()
    {
        if (!$this->request->isAJAX()) {
            return $this->failForbidden('Access denied');
        }
        
        $postData = $this->request->getPost();
        
        try {
            $model = new \App\Models\ReportDefinitionModel();
            $reportId = $model->saveEnhancedReportDefinition($postData);
            
            return $this->respondCreated([
                'status' => 'success',
                'message' => 'Enhanced report saved successfully',
                'report_id' => $reportId
            ]);
            
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
    
    /**
     * Get complex ORDER BY options
     */
    public function getOrderByOptions()
    {
        $options = [
            'types' => [
                'COLUMN' => 'Simple Column',
                'EXPRESSION' => 'SQL Expression',
                'CASE' => 'CASE WHEN Expression',
                'FUNCTION' => 'Function Result'
            ],
            'directions' => ['ASC', 'DESC'],
            'nulls_orders' => ['NULLS FIRST', 'NULLS LAST', null => 'Default'],
            'functions' => [
                'LENGTH' => 'LENGTH(column)',
                'UPPER' => 'UPPER(column)',
                'LOWER' => 'LOWER(column)',
                'DATE' => 'DATE(column)',
                'YEAR' => 'YEAR(column)',
                'MONTH' => 'MONTH(column)',
                'DAY' => 'DAY(column)',
                'ABS' => 'ABS(column)',
                'ROUND' => 'ROUND(column, decimals)',
                'CONCAT' => 'CONCAT(col1, col2)'
            ]
        ];
        
        return $this->respond($options);
    }
    
    /**
     * Get complex GROUP BY options
     */
    public function getGroupByOptions()
    {
        $options = [
            'types' => [
                'COLUMN' => 'Simple Column',
                'EXPRESSION' => 'SQL Expression',
                'ROLLUP' => 'WITH ROLLUP',
                'CUBE' => 'WITH CUBE',
                'GROUPING_SETS' => 'GROUPING SETS'
            ],
            'functions' => [
                'YEAR' => 'YEAR(date_column)',
                'MONTH' => 'MONTH(date_column)',
                'DATE' => 'DATE(datetime_column)',
                'WEEK' => 'WEEK(date_column)',
                'QUARTER' => 'QUARTER(date_column)',
                'FLOOR' => 'FLOOR(number_column / interval) * interval',
                'CASE' => 'CASE WHEN condition THEN value END'
            ]
        ];
        
        return $this->respond($options);
    }
    
    /**
     * Preview complex SQL
     */
    public function previewComplexSql()
    {
        if (!$this->request->isAJAX()) {
            return $this->failForbidden('Access denied');
        }
        
        $postData = $this->request->getPost();
        
        try {
            // Create temporary enhanced report object
            $report = [
                'base_table' => $postData['base_table'],
                'columns' => $postData['columns'] ?? [],
                'joins' => $postData['joins'] ?? [],
                'conditions' => $postData['conditions'] ?? [],
                'orders' => $postData['orders'] ?? [],
                'groups' => $postData['groups'] ?? [],
                'having' => $postData['having'] ?? [],
                'case_mappings' => $postData['case_mappings'] ?? []
            ];
            
            $queryBuilder = new \App\Libraries\EnhancedQueryBuilder();
            $result = $queryBuilder->buildEnhancedQuery($report, $postData['parameters'] ?? []);
            
            return $this->respond([
                'status' => 'success',
                'sql' => $result['sql'],
                'params' => $result['params']
            ]);
            
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}