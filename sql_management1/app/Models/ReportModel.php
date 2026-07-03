<?php
// app/Models/ReportModel.php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\ReportEntity;

class ReportModel extends Model
{
    protected $table = 'advanced_reports';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = ReportEntity::class;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'report_name', 'description', 'report_type', 'base_table', 'base_tables',
        'joins_config', 'columns_config', 'calculated_fields', 'filters_config',
        'grouping_config', 'sorting_config', 'subqueries_config', 'custom_sql',
        'access_roles', 'export_formats', 'created_by', 'is_active'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    protected $validationRules = [
        'report_name' => 'required|min_length[3]|max_length[255]',
        'report_type' => 'required|in_list[simple,advanced,custom_sql]',
        'created_by' => 'required|numeric'
    ];
    
    protected $validationMessages = [];
    protected $skipValidation = false;

    /**
     * Get active reports for a role
     */
    public function getReportsForRole(string $role): array
    {
        $reports = $this->where('is_active', 1)->findAll();
        
        // Filter by role access
        return array_filter($reports, function($report) use ($role) {
            return $report->canAccess($role);
        });
    }

    /**
     * Log report execution
     */
    public function logExecution(int $reportId, array $parameters, int $userId, array $result): bool
    {
        $executionModel = new class extends Model {
            protected $table = 'report_executions';
            protected $primaryKey = 'id';
            protected $allowedFields = [
                'report_id', 'parameters', 'executed_by',
                'execution_time', 'record_count', 'status',
                'error_message', 'export_format', 'file_path',
                'executed_at'
            ];
            protected $useTimestamps = false;
        };
        
        return $executionModel->insert([
            'report_id' => $reportId,
            'parameters' => json_encode($parameters),
            'executed_by' => $userId,
            'execution_time' => $result['execution_time'] ?? null,
            'record_count' => $result['record_count'] ?? 0,
            'status' => $result['status'] ?? 'success',
            'error_message' => $result['error_message'] ?? null,
            'export_format' => $result['export_format'] ?? null,
            'file_path' => $result['file_path'] ?? null,
            'executed_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get execution history
     */
    public function getExecutionHistory(int $reportId, int $limit = 50): array
    {
        $db = db_connect();
        return $db->table('report_executions')
            ->where('report_id', $reportId)
            ->orderBy('executed_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Get reports by type
     */
    public function getReportsByType(string $type): array
    {
        return $this->where('report_type', $type)
                    ->where('is_active', 1)
                    ->findAll();
    }
}