<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportDefinitionModel extends Model
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
     * Get complete report definition with all related data
     */
    public function getCompleteDefinition(int $reportId): ?array
    {
        // Get main report
        $report = $this->find($reportId);
        if (!$report) {
            return null;
        }
        
        // Get joins
        $report['joins'] = $this->getJoins($reportId);
        
        // Get columns
        $report['columns'] = $this->getColumns($reportId);
        
        // Get filters
        $report['filters'] = $this->getFilters($reportId);
        
        // Get groups
        $report['groups'] = $this->getGroups($reportId);
        
        // Get orders
        $report['orders'] = $this->getOrders($reportId);
        
        // Get parameters
        $report['parameters'] = $this->getParameters($reportId);
        
        return $report;
    }
    
    /**
     * Get report joins
     */
    public function getJoins(int $reportId): array
    {
        $db = db_connect();
        return $db->table('report_joins')
            ->where('report_id', $reportId)
            ->orderBy('join_order', 'ASC')
            ->get()
            ->getResultArray();
    }
    
    /**
     * Get report columns
     */
    public function getColumns(int $reportId): array
    {
        $db = db_connect();
        return $db->table('report_columns')
            ->where('report_id', $reportId)
            ->orderBy('display_order', 'ASC')
            ->get()
            ->getResultArray();
    }
    
    /**
     * Get report filters
     */
    public function getFilters(int $reportId): array
    {
        $db = db_connect();
        return $db->table('report_filters')
            ->where('report_id', $reportId)
            ->orderBy('condition_type', 'ASC')
            ->orderBy('filter_order', 'ASC')
            ->get()
            ->getResultArray();
    }
    
    /**
     * Get report groups
     */
    public function getGroups(int $reportId): array
    {
        $db = db_connect();
        return $db->table('report_groups')
            ->where('report_id', $reportId)
            ->orderBy('group_order', 'ASC')
            ->get()
            ->getResultArray();
    }
    
    /**
     * Get report orders
     */
    public function getOrders(int $reportId): array
    {
        $db = db_connect();
        return $db->table('report_orders')
            ->where('report_id', $reportId)
            ->orderBy('order_priority', 'ASC')
            ->get()
            ->getResultArray();
    }
    
    /**
     * Get report parameters
     */
    public function getParameters(int $reportId): array
    {
        $db = db_connect();
        return $db->table('report_parameters')
            ->where('report_id', $reportId)
            ->orderBy('parameter_key', 'ASC')
            ->get()
            ->getResultArray();
    }
    
    /**
     * Get template by name
     */
    public function getTemplateByName(string $templateName): ?array
    {
        return $this->where('is_template', 1)
            ->where('report_name', $templateName)
            ->first();
    }
    
    /**
     * Create template from existing report
     */
    public function createTemplate(int $reportId, string $templateName, string $description = null): bool
    {
        $definition = $this->getCompleteDefinition($reportId);
        
        if (!$definition) {
            return false;
        }
        
        $db = db_connect();
        $db->transStart();
        
        try {
            // Create new report as template
            $templateId = $db->table('reports')->insert([
                'report_name' => $templateName,
                'base_table' => $definition['base_table'],
                'description' => $description ?: "Template: {$definition['report_name']}",
                'is_template' => 1,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $templateId = $db->insertID();
            
            // Copy joins
            foreach ($definition['joins'] as $join) {
                unset($join['id']);
                $join['report_id'] = $templateId;
                $db->table('report_joins')->insert($join);
            }
            
            // Copy columns
            foreach ($definition['columns'] as $column) {
                unset($column['id']);
                $column['report_id'] = $templateId;
                $db->table('report_columns')->insert($column);
            }
            
            // Copy filters
            foreach ($definition['filters'] as $filter) {
                unset($filter['id']);
                $filter['report_id'] = $templateId;
                $db->table('report_filters')->insert($filter);
            }
            
            // Copy groups
            foreach ($definition['groups'] as $group) {
                unset($group['id']);
                $group['report_id'] = $templateId;
                $db->table('report_groups')->insert($group);
            }
            
            // Copy orders
            foreach ($definition['orders'] as $order) {
                unset($order['id']);
                $order['report_id'] = $templateId;
                $db->table('report_orders')->insert($order);
            }
            
            // Copy parameters
            foreach ($definition['parameters'] as $param) {
                unset($param['id']);
                $param['report_id'] = $templateId;
                $db->table('report_parameters')->insert($param);
            }
            
            $db->transComplete();
            
            return $db->transStatus();
            
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Failed to create template: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all active reports
     */
    public function getActiveReports(): array
    {
        return $this->where('is_active', 1)
            ->orderBy('report_name', 'ASC')
            ->findAll();
    }
    
    /**
     * Get all templates
     */
    public function getTemplates(): array
    {
        return $this->where('is_template', 1)
            ->where('is_active', 1)
            ->orderBy('report_name', 'ASC')
            ->findAll();
    }
    
    /**
     * Validate report definition
     */
    public function validateDefinition(array $definition): array
    {
        $errors = [];
        
        // Check required fields
        if (empty($definition['report_name'])) {
            $errors[] = 'Report name is required';
        }
        
        if (empty($definition['base_table'])) {
            $errors[] = 'Base table is required';
        }
        
        // Check base table exists
        if (!empty($definition['base_table'])) {
            $db = db_connect();
            if (!$db->tableExists($definition['base_table'])) {
                $errors[] = "Base table '{$definition['base_table']}' does not exist";
            }
        }
        
        // Validate joins
        foreach ($definition['joins'] ?? [] as $index => $join) {
            if (empty($join['table_name'])) {
                $errors[] = "Join #{$index}: Table name is required";
            }
            if (empty($join['join_condition'])) {
                $errors[] = "Join #{$index}: Join condition is required";
            }
        }
        
        // Validate columns
        if (empty($definition['columns'])) {
            $errors[] = 'At least one column is required';
        } else {
            $aliases = [];
            foreach ($definition['columns'] as $index => $column) {
                if (empty($column['alias'])) {
                    $errors[] = "Column #{$index}: Alias is required";
                }
                if (empty($column['column_expression'])) {
                    $errors[] = "Column #{$index}: Expression is required";
                }
                
                // Check for duplicate aliases
                if (isset($aliases[$column['alias']])) {
                    $errors[] = "Duplicate column alias: '{$column['alias']}'";
                }
                $aliases[$column['alias']] = true;
            }
        }
        
        return $errors;
    }
    
    /**
     * Save complete report definition
     */
    public function saveCompleteDefinition(array $definition): bool
    {
        $errors = $this->validateDefinition($definition);
        if (!empty($errors)) {
            throw new \RuntimeException(implode(', ', $errors));
        }
        
        $db = db_connect();
        $db->transStart();
        
        try {
            // Save main report
            $reportData = [
                'report_name' => $definition['report_name'],
                'base_table' => $definition['base_table'],
                'description' => $definition['description'] ?? null,
                'is_active' => $definition['is_active'] ?? 1,
                'is_template' => $definition['is_template'] ?? 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if (empty($definition['id'])) {
                $reportData['created_at'] = date('Y-m-d H:i:s');
                $db->table('reports')->insert($reportData);
                $reportId = $db->insertID();
            } else {
                $reportId = $definition['id'];
                $db->table('reports')->where('id', $reportId)->update($reportData);
                
                // Delete existing relations
                $this->deleteReportRelations($reportId);
            }
            
            // Save joins
            foreach ($definition['joins'] ?? [] as $join) {
                $join['report_id'] = $reportId;
                $db->table('report_joins')->insert($join);
            }
            
            // Save columns
            foreach ($definition['columns'] ?? [] as $index => $column) {
                $column['report_id'] = $reportId;
                $column['display_order'] = $index;
                $db->table('report_columns')->insert($column);
            }
            
            // Save filters
            foreach ($definition['filters'] ?? [] as $filter) {
                $filter['report_id'] = $reportId;
                $db->table('report_filters')->insert($filter);
            }
            
            // Save groups
            foreach ($definition['groups'] ?? [] as $group) {
                $group['report_id'] = $reportId;
                $db->table('report_groups')->insert($group);
            }
            
            // Save orders
            foreach ($definition['orders'] ?? [] as $order) {
                $order['report_id'] = $reportId;
                $db->table('report_orders')->insert($order);
            }
            
            // Save parameters
            foreach ($definition['parameters'] ?? [] as $param) {
                $param['report_id'] = $reportId;
                $db->table('report_parameters')->insert($param);
            }
            
            $db->transComplete();
            
            return $db->transStatus();
            
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Failed to save report definition: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete report relations
     */
    private function deleteReportRelations(int $reportId): void
    {
        $db = db_connect();
        $tables = [
            'report_joins',
            'report_columns',
            'report_filters',
            'report_groups',
            'report_orders',
            'report_parameters'
        ];
        
        foreach ($tables as $table) {
            $db->table($table)->where('report_id', $reportId)->delete();
        }
    }
}