<?php

namespace App\Controllers;

use App\Models\ReportModel;
use CodeIgniter\API\ResponseTrait;

class ReportController extends BaseController
{
    use ResponseTrait;

    protected $reportModel;
    protected $helpers = ['form', 'url'];

    public function __construct()
    {
        $this->reportModel = new ReportModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Step 2: Report Listing
     */
    public function index()
    {
        $data = [
            'title' => 'Report Management',
            'reports' => $this->reportModel->where('is_active', 1)->findAll(),
        ];
        
        return view('reports/index', $data);
    }

    /**
     * Step 1: Store Report (Create)
     */
    public function create()
    {


        $data = [
            'title' => 'Create New Report',
            'validation' => \Config\Services::validation(),
        ];
        
        return view('reports/create', $data);
    }

    public function store()
    {
        // Validate input
        $rules = [
            'report_name' => 'required|min_length[3]|max_length[255]',
            'description' => 'permit_empty',
            'base_query' => 'required',
        ];
    
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
    
        $postData = $this->request->getPost();
        
        // Clean up the SQL query
        $baseQuery = trim($postData['base_query']);
        // Remove trailing semicolon if present
        $baseQuery = rtrim($baseQuery, ';');
        
        // Parse query to extract components
        $components = $this->reportModel->parseQuery($baseQuery);
        
        // Debug: Log the parsed components
        log_message('info', 'Parsed components: ' . print_r($components, true));
        
        // Prepare data for saving
        $reportData = [
            'report_name' => $postData['report_name'],
            'description' => $postData['description'],
            'base_query' => $baseQuery,
            'selected_columns' => json_encode($components['selected_columns'], JSON_UNESCAPED_UNICODE),
            'where_conditions' => json_encode($components['where_conditions'], JSON_UNESCAPED_UNICODE),
            'group_by' => json_encode($components['group_by'], JSON_UNESCAPED_UNICODE),
            'order_by' => json_encode($components['order_by'], JSON_UNESCAPED_UNICODE),
            'filter_parameters' => json_encode($components['filter_parameters'], JSON_UNESCAPED_UNICODE),
            'created_by' => session()->get('user_id') ?? 1, // Default to 1 if no session
            'is_active' => 1
        ];
    
        // Debug: Log the data to be saved
        log_message('info', 'Report data to save: ' . print_r($reportData, true));
        
        if ($this->reportModel->save($reportData)) {
            return redirect()->to('/reports')->with('success', 'Report created successfully');
        } else {
            $errors = $this->reportModel->errors();
            log_message('error', 'Save failed: ' . print_r($errors, true));
            return redirect()->back()->withInput()->with('error', 'Failed to create report: ' . implode(', ', $errors));
        }
    }

    /**
     * Step 3: Edit Report
     */
    public function edit($id)
    {
        $report = $this->reportModel->find($id);
        if (!$report) {
            return redirect()->to('/reports')->with('error', 'Report not found');
        }
    
        $data = [
            'title' => 'Edit Report',
            'report' => $report,
            'selected_columns' => json_decode($report['selected_columns'] ?? '[]', true),
            'where_conditions' => json_decode($report['where_conditions'] ?? '[]', true),
            'group_by' => json_decode($report['group_by'] ?? '[]', true),
            'order_by' => json_decode($report['order_by'] ?? '[]', true),
            'filter_parameters' => json_decode($report['filter_parameters'] ?? '[]', true),
            'validation' => \Config\Services::validation(),
        ];
        
        return view('reports/edit', $data);
    }

    public function update($id)
    {
            $rules = [
                'report_name' => 'required|min_length[3]|max_length[255]',
                'description' => 'permit_empty',
                'base_query' => 'required',
            ];
    
            if (!$this->validate($rules)) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }
    
            $postData = $this->request->getPost();
            
            // Clean up the SQL query
            $baseQuery = trim($postData['base_query']);
            $baseQuery = rtrim($baseQuery, ';');
            
            // Parse query to extract components
            $components = $this->reportModel->parseQuery($baseQuery);
            
            // Prepare update data
            $updateData = [
                'id' => $id,
                'report_name' => $postData['report_name'],
                'description' => $postData['description'],
                'base_query' => $baseQuery,
                'selected_columns' => json_encode($components['selected_columns'], JSON_UNESCAPED_UNICODE),
                'where_conditions' => json_encode($components['where_conditions'], JSON_UNESCAPED_UNICODE),
                'group_by' => json_encode($components['group_by'], JSON_UNESCAPED_UNICODE),
                'order_by' => json_encode($components['order_by'], JSON_UNESCAPED_UNICODE),
                'filter_parameters' => json_encode($components['filter_parameters'], JSON_UNESCAPED_UNICODE),
                'updated_by' => session()->get('user_id') ?? 1,
            ];
    
            if ($this->reportModel->save($updateData)) {
                return redirect()->to('/reports')->with('success', 'Report updated successfully');
            } else {
                $errors = $this->reportModel->errors();
                return redirect()->back()->withInput()->with('error', 'Failed to update report: ' . implode(', ', $errors));
            }
    }

    /**
     * Step 4: Preview Report
     */
    // public function preview($id)
    // {
    //     $report = $this->reportModel->find($id);
    //     if (!$report) {
    //         return redirect()->to('/reports')->with('error', 'Report not found');
    //     }

    //     $results = [];
    //     $appliedFilters = [];

    //     if ($this->request->getMethod() === 'POST') {

    //         // Get filter values from form
    //         $filters = $this->request->getPost('filters') ?? [];
    //         $appliedFilters = $filters;
            
    //         try {
    //             $results = $this->reportModel->executeReport($id, $filters);
    //         } catch (\Exception $e) {
    //             log_message('error', 'Report execution error: ' . $e->getMessage());
    //             return redirect()->back()->with('error', 'Error executing report: ' . $e->getMessage());
    //         }
    //     } else {
    //         // Initial preview with default parameters
    //         try {
    //             $results = $this->reportModel->executeReport($id);
    //         } catch (\Exception $e) {
    //             log_message('error', 'Report execution error: ' . $e->getMessage());
    //         }
    //     }

    //     $data = [
    //         'title' => 'Preview Report: ' . $report['report_name'],
    //         'report' => $report,
    //         'results' => $results,
    //         'columns' => !empty($results) ? array_keys($results[0]) : [],
    //         'selected_columns' => json_decode($report['selected_columns'] ?? '[]', true),
    //         'where_conditions' => json_decode($report['where_conditions'] ?? '[]', true),
    //         'group_by' => json_decode($report['group_by'] ?? '[]', true),
    //         'order_by' => json_decode($report['order_by'] ?? '[]', true),
    //         'filter_parameters' => json_decode($report['filter_parameters'] ?? '[]', true),
    //         'applied_filters' => $appliedFilters,
    //     ];
        
    //     return view('reports/preview', $data);
    // }

    public function preview($id)
    {
        $report = $this->reportModel->find($id);
        if (!$report) {
            return redirect()->to('/reports')->with('error', 'Report not found');
        }
    
        $results = [];
        $appliedFilters = [];
    
        if ($this->request->getMethod() === 'POST') {
            // Get all POST data
            $postData = $this->request->getPost();
            
            // Debug: log all post data
            log_message('info', 'POST data: ' . print_r($postData, true));
            
            // Initialize filters array with report_id
            $filters = [
                'report_id' => $id,
            ];
            
            // Extract filters from the filters array
            if (isset($postData['filters']) && is_array($postData['filters'])) {
                foreach ($postData['filters'] as $key => $value) {
                    if (empty($value) && $value !== '0' && $value !== 0) {
                        continue;
                    }
                    $filters[$key] = $value;
                }
            }
            
            // Add GROUP BY if exists (outside filters array)
            if (isset($postData['group_by']) && !empty($postData['group_by'])) {
                $filters['group_by'] = $postData['group_by'];
            }
            
            // Add ORDER BY if exists (outside filters array)
            if (isset($postData['order_by']) && !empty($postData['order_by'])) {
                $filters['order_by'] = $postData['order_by'];
            }
            
            // Add report_id from POST if exists
            if (isset($postData['report_id'])) {
                $filters['report_id'] = $postData['report_id'];
            }
            
            $appliedFilters = $filters;
            
            // Debug: log the filters array
            log_message('info', 'Final filters: ' . print_r($filters, true));
            
            try {
                $results = $this->reportModel->executeReport($id, $filters);
            } catch (\Exception $e) {
                log_message('error', 'Report execution error: ' . $e->getMessage());
                return redirect()->back()->with('error', 'Error executing report: ' . $e->getMessage());
            }
        } else {
            // Initial preview with default parameters
            try {
                $results = $this->reportModel->executeReport($id);
            } catch (\Exception $e) {
                log_message('error', 'Report execution error: ' . $e->getMessage());
            }
        }
    
        $data = [
            'title' => 'Preview Report: ' . $report['report_name'],
            'report' => $report,
            'results' => $results,
            'columns' => !empty($results) ? array_keys($results[0]) : [],
            'selected_columns' => json_decode($report['selected_columns'] ?? '[]', true),
            'where_conditions' => json_decode($report['where_conditions'] ?? '[]', true),
            'group_by' => json_decode($report['group_by'] ?? '[]', true),
            'order_by' => json_decode($report['order_by'] ?? '[]', true),
            'filter_parameters' => json_decode($report['filter_parameters'] ?? '[]', true),
            'applied_filters' => $appliedFilters,
        ];
        
        return view('reports/preview', $data);
    }

    /**
     * Export Report Data
     */
    // public function export($id, $format = 'csv')
    // {
    //     $report = $this->reportModel->find($id);
    //     if (!$report) {
    //         return redirect()->to('/reports')->with('error', 'Report not found');
    //     }

    //     // Get filters if any
    //     $filters = $this->request->getGet('filters') ?? [];
        
    //     try {
    //         $results = $this->reportModel->executeReport($id, $filters);
    //     } catch (\Exception $e) {
    //         return redirect()->back()->with('error', 'Error exporting report: ' . $e->getMessage());
    //     }

    //     if (empty($results)) {
    //         return redirect()->back()->with('error', 'No data to export');
    //     }

    //     switch ($format) {
    //         case 'csv':
    //             return $this->exportToCSV($results, $report['report_name']);
    //         case 'excel':
    //             return $this->exportToExcel($results, $report['report_name']);
    //         case 'json':
    //             return $this->exportToJSON($results, $report['report_name']);
    //         case 'pdf':
    //             return $this->exportToPDF($results, $report['report_name']);
    //         default:
    //             return $this->exportToCSV($results, $report['report_name']);
    //     }
    // }
    public function export($id, $format = 'csv')
{
    $report = $this->reportModel->find($id);
    if (!$report) {
        return redirect()->to('/reports')->with('error', 'Report not found');
    }

    // Get filters from GET parameters
    $filters = $this->request->getGet();
    
    // Remove CSRF token if present
    if (isset($filters['csrf_test_name'])) {
        unset($filters['csrf_test_name']);
    }
    
    // Parse array parameters (group_by[0], order_by[0][column], etc.)
    $parsedFilters = [];
    
    foreach ($filters as $key => $value) {
        // Parse array keys like group_by[0]
        if (preg_match('/^(\w+)\[(\d+)\]$/', $key, $matches)) {
            $arrayKey = $matches[1];
            $index = $matches[2];
            if (!isset($parsedFilters[$arrayKey])) {
                $parsedFilters[$arrayKey] = [];
            }
            $parsedFilters[$arrayKey][$index] = $value;
        }
        // Parse nested array keys like order_by[0][column]
        elseif (preg_match('/^(\w+)\[(\d+)\]\[(\w+)\]$/', $key, $matches)) {
            $arrayKey = $matches[1];
            $index = $matches[2];
            $subKey = $matches[3];
            if (!isset($parsedFilters[$arrayKey])) {
                $parsedFilters[$arrayKey] = [];
            }
            if (!isset($parsedFilters[$arrayKey][$index])) {
                $parsedFilters[$arrayKey][$index] = [];
            }
            $parsedFilters[$arrayKey][$index][$subKey] = $value;
        }
        // Handle simple keys
        else {
            $parsedFilters[$key] = $value;
        }
    }
    
    // Re-index arrays to ensure they're sequential
    foreach ($parsedFilters as $key => &$value) {
        if (is_array($value) && !empty($value)) {
            // Check if it's a nested array (order_by)
            $isNested = false;
            foreach ($value as $item) {
                if (is_array($item)) {
                    $isNested = true;
                    break;
                }
            }
            
            if (!$isNested) {
                // For simple arrays like group_by
                $value = array_values($value);
            }
        }
    }
    
    // Debug: log the parsed filters
    log_message('info', 'Export filters: ' . print_r($parsedFilters, true));
    
    try {
        $results = $this->reportModel->executeReport($id, $parsedFilters);
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Error exporting report: ' . $e->getMessage());
    }

    if (empty($results)) {
        return redirect()->back()->with('error', 'No data to export');
    }

    switch ($format) {
        case 'csv':
            return $this->exportToCSV($results, $report['report_name']);
        case 'excel':
            return $this->exportToExcel($results, $report['report_name']);
        case 'json':
            return $this->exportToJSON($results, $report['report_name']);
        case 'pdf':
            return $this->exportToPDF($results, $report['report_name']);
        default:
            return $this->exportToCSV($results, $report['report_name']);
    }
}

    /**
     * Export to CSV
     */
    private function exportToCSV($data, $filename)
    {
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename) . '_' . date('Ymd_His') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add headers
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            
            // Add data rows
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export to Excel (using PhpSpreadsheet if available)
     */
    private function exportToExcel($data, $filename)
    {
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename) . '_' . date('Ymd_His') . '.xlsx';
        
        // Simple CSV fallback if PhpSpreadsheet not available
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array_keys($data[0]));
        
        foreach ($data as $row) {
            fputcsv($output, $row, "\t"); // Tab separated for Excel
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export to JSON
     */
    private function exportToJSON($data, $filename)
    {
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename) . '_' . date('Ymd_His') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Step 5: Delete Report
     */
    public function delete($id)
    {
            $report = $this->reportModel->find($id);
            
            if (!$report) {
                return redirect()->to('/reports')->with('error', 'Report not found');
            }

            // Check dependencies if any
            // Add your dependency checks here
            
            if ($this->reportModel->delete($id)) {
                return redirect()->to('/reports')->with('success', 'Report deleted successfully');
            } else {
                return redirect()->to('/reports')->with('error', 'Failed to delete report');
            }

        return redirect()->to('/reports');
    }

    /**
     * Validate SQL Query
     */
    public function validateQuery()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Access denied']);
        }

        $query = $this->request->getPost('query');
        
        if (empty($query)) {
            return $this->respond(['valid' => false, 'error' => 'Query is empty']);
        }

        try {
            // Basic SQL validation
            if (!preg_match('/^SELECT/i', $query)) {
                return $this->respond(['valid' => false, 'error' => 'Only SELECT queries are allowed']);
            }

            // Check for dangerous operations
            $dangerousKeywords = ['DROP', 'DELETE', 'UPDATE', 'INSERT', 'GRANT', 'REVOKE', 'EXEC', 'EXECUTE'];
            foreach ($dangerousKeywords as $keyword) {
                if (stripos($query, $keyword) !== false && 
                    stripos($query, $keyword . ' TABLE') === false && // Allow DROP TABLE in subqueries?
                    stripos($query, $keyword . ' DATABASE') === false) {
                    return $this->respond(['valid' => false, 'error' => 'Query contains dangerous operation: ' . $keyword]);
                }
            }

            // Try to parse the query
            $components = $this->reportModel->parseQuery($query);
            
            return $this->respond([
                'valid' => true,
                'components' => $components
            ]);

        } catch (\Exception $e) {
            return $this->respond(['valid' => false, 'error' => 'Invalid SQL: ' . $e->getMessage()]);
        }
    }
}