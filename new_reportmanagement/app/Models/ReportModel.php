<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportModel extends Model
{
    protected $table = 'reports';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    
    protected $allowedFields = [
        'report_name', 'base_table', 'description', 
        'is_active', 'is_template', 'created_at', 'updated_at'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    
    protected $validationRules = [
        'report_name' => 'required|min_length[3]|max_length[255]',
        'base_table' => 'required|min_length[1]|max_length[255]',
    ];
    
    protected $validationMessages = [];
    protected $skipValidation = false;
    
    /**
     * Get reports with pagination
     */
    public function getReportsPaginated(int $perPage = 20, int $page = 1): array
    {
        $offset = ($page - 1) * $perPage;
        
        $reports = $this->where('is_template', 0)
            ->orderBy('created_at', 'DESC')
            ->findAll($perPage, $offset);
        
        $total = $this->where('is_template', 0)->countAllResults();
        
        return [
            'reports' => $reports,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Search reports by name or description
     */
    public function searchReports(string $keyword, int $limit = 20): array
    {
        return $this->like('report_name', $keyword)
            ->orLike('description', $keyword)
            ->where('is_template', 0)
            ->where('is_active', 1)
            ->orderBy('report_name', 'ASC')
            ->findAll($limit);
    }
    
    /**
     * Get recently created reports
     */
    public function getRecentReports(int $limit = 10): array
    {
        return $this->where('is_template', 0)
            ->where('is_active', 1)
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }
    
    /**
     * Get report statistics
     */
    public function getReportStats(): array
    {
        $db = db_connect();
        
        $total = $this->where('is_template', 0)->countAllResults();
        $active = $this->where('is_template', 0)->where('is_active', 1)->countAllResults();
        $templates = $this->where('is_template', 1)->countAllResults();
        
        // Get reports by base table
        $byTable = $db->table($this->table)
            ->select('base_table, COUNT(*) as count')
            ->where('is_template', 0)
            ->groupBy('base_table')
            ->orderBy('count', 'DESC')
            ->get()
            ->getResultArray();
        
        // Get recent activity
        $recent = $db->table($this->table)
            ->select('report_name, created_at')
            ->where('is_template', 0)
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();
        
        return [
            'total_reports' => $total,
            'active_reports' => $active,
            'templates' => $templates,
            'by_table' => $byTable,
            'recent' => $recent
        ];
    }
    
    /**
     * Clone report
     */
    public function cloneReport(int $reportId, string $newName): bool
    {
        $definitionModel = new ReportDefinitionModel();
        $definition = $definitionModel->getCompleteDefinition($reportId);
        
        if (!$definition) {
            return false;
        }
        
        // Update name and reset template flag
        $definition['report_name'] = $newName;
        $definition['is_template'] = 0;
        $definition['id'] = null;
        
        return $definitionModel->saveCompleteDefinition($definition);
    }
    
    /**
     * Export report definition as JSON
     */
    public function exportDefinition(int $reportId): string
    {
        $definitionModel = new ReportDefinitionModel();
        $definition = $definitionModel->getCompleteDefinition($reportId);
        
        if (!$definition) {
            throw new \RuntimeException("Report not found");
        }
        
        // Remove IDs and timestamps
        unset($definition['id'], $definition['created_at'], $definition['updated_at'], $definition['deleted_at']);
        
        foreach ($definition['joins'] ?? [] as &$join) {
            unset($join['id'], $join['created_at']);
        }
        
        foreach ($definition['columns'] ?? [] as &$column) {
            unset($column['id'], $column['created_at']);
        }
        
        foreach ($definition['filters'] ?? [] as &$filter) {
            unset($filter['id'], $filter['created_at']);
        }
        
        foreach ($definition['groups'] ?? [] as &$group) {
            unset($group['id'], $group['created_at']);
        }
        
        foreach ($definition['orders'] ?? [] as &$order) {
            unset($order['id'], $order['created_at']);
        }
        
        foreach ($definition['parameters'] ?? [] as &$param) {
            unset($param['id'], $param['created_at']);
        }
        
        return json_encode($definition, JSON_PRETTY_PRINT);
    }
    
    /**
     * Import report definition from JSON
     */
    public function importDefinition(string $json, string $reportName = null): bool
    {
        $definition = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON: ' . json_last_error_msg());
        }
        
        if ($reportName) {
            $definition['report_name'] = $reportName;
        }
        
        $definitionModel = new ReportDefinitionModel();
        return $definitionModel->saveCompleteDefinition($definition);
    }
}