<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Libraries\DynamicQueryBuilder;
use App\Libraries\EnhancedQueryBuilder;

class ReportModel extends Model
{
    protected $table = 'reports';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    
    protected $allowedFields = [
        'report_name', 'base_table', 'description', 
        'is_template', 'created_at', 'updated_at'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    
    protected $validationRules = [
        'report_name' => 'required|min_length[3]|max_length[255]',
        'base_table' => 'required|max_length[255]'
    ];
    
    protected $validationMessages = [];
    protected $skipValidation = false;
    
    /**
     * Get complete report with all enhanced relationships
     */
    public function getReportWithDetails($id)
    {
        $report = $this->find($id);
        
        if (!$report) {
            return null;
        }
        
        $db = \Config\Database::connect();
        
        // Get enhanced relationships
        $report['columns'] = $this->getReportColumns($id);
        $report['joins'] = $this->getReportJoins($id);
        $report['conditions'] = $this->getReportConditions($id);
        $report['orders'] = $this->getReportOrders($id);
        $report['groups'] = $this->getReportGroups($id);
        $report['having'] = $this->getReportHaving($id);
        $report['parameters'] = $this->getReportParameters($id);
        $report['case_mappings'] = $this->getCaseMappings($id);
        
        return $report;
    }
    
    private function getReportColumns($reportId)
    {
        $db = \Config\Database::connect();
        return $db->table('report_columns')
            ->where('report_id', $reportId)
            ->orderBy('column_order', 'ASC')
            ->get()
            ->getResultArray();
    }
    
    private function getReportJoins($reportId)
    {
        $db = \Config\Database::connect();
        return $db->table('report_joins')
            ->where('report_id', $reportId)
            ->orderBy('join_order', 'ASC')
            ->get()
            ->getResultArray();
    }
    
    private function getReportConditions($reportId)
    {
        $db = \Config\Database::connect();
        return $db->table('report_conditions')
            ->where('report_id', $reportId)
            ->where('condition_type', 'WHERE')
            ->orderBy('condition_order', 'ASC')
            ->get()
            ->getResultArray();
    }
    
    private function getReportOrders($reportId)
    {
        $db = \Config\Database::connect();
        return $db->table('report_orders')
            ->where('report_id', $reportId)
            ->orderBy('order_sequence', 'ASC')
            ->get()
            ->getResultArray();
    }
    
    private function getReportGroups($reportId)
    {
        $db = \Config\Database::connect();
        return $db->table('report_groups')
            ->where('report_id', $reportId)
            ->orderBy('group_order', 'ASC')
            ->get()
            ->getResultArray();
    }
    
    private function getReportHaving($reportId)
    {
        $db = \Config\Database::connect();
        return $db->table('report_having')
            ->where('report_id', $reportId)
            ->orderBy('having_order', 'ASC')
            ->get()
            ->getResultArray();
    }
    
    private function getReportParameters($reportId)
    {
        $db = \Config\Database::connect();
        return $db->table('report_parameters')
            ->where('report_id', $reportId)
            ->orderBy('parameter_order', 'ASC')
            ->get()
            ->getResultArray();
    }
    
    private function getCaseMappings($reportId)
    {
        $db = \Config\Database::connect();
        return $db->table('report_case_mappings')
            ->where('report_id', $reportId)
            ->orderBy('case_order', 'ASC')
            ->get()
            ->getResultArray();
    }

        /**
     * Get all reports with pagination
     */
    public function getAllReports($page = 1, $perPage = 20, $search = '')
    {
        $builder = $this->builder();
        
        if ($search) {
            $builder->groupStart()
                ->like('report_name', $search)
                ->orLike('description', $search)
                ->orLike('base_table', $search)
                ->groupEnd();
        }
        
        $total = $builder->countAllResults(false);
        
        $reports = $builder->select('*')
            ->where('deleted_at', null)
            ->orderBy('created_at', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()
            ->getResultArray();
        
        return [
            'data' => $reports,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Execute report with complex ordering and grouping
     */
    public function executeReport($reportId, $parameters = [], $limit = null, $offset = 0)
    {
        $report = $this->getReportWithDetails($reportId);
        
        if (!$report) {
            throw new \Exception("Report not found");
        }
        
        $queryBuilder = new EnhancedQueryBuilder();
        
        // Build the enhanced query
        $query = $queryBuilder->buildEnhancedQuery($report, $parameters);
        
        // Get database connection
        $db = \Config\Database::connect();
        
        // Execute count query for pagination
        $countQuery = "SELECT COUNT(*) as total FROM ({$query['sql']}) as count_table";
        try {
            $countResult = $db->query($countQuery, $query['params'])->getRow();
            $totalRows = $countResult->total;
        } catch (\Exception $e) {
            $totalRows = 0;
        }
        
        // Apply limit and offset
        $finalSql = $query['sql'];
        if ($limit) {
            $finalSql .= " LIMIT ? OFFSET ?";
            $query['params'][] = $limit;
            $query['params'][] = $offset;
        }
        
        // Execute main query
        $result = $db->query($finalSql, $query['params'])->getResultArray();
        
        return [
            'data' => $result,
            'total' => $totalRows,
            'sql' => $finalSql,
            'params' => $query['params']
        ];
    }
}