<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportDefinitionModel extends Model
{
    protected $table = 'reports';
    protected $primaryKey = 'id';
    
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    
    protected $allowedFields = [
        'report_name', 'base_table', 'description', 'is_template'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function saveEnhancedReportDefinition($data)
    {
        $db = \Config\Database::connect();
        $db->transStart();
        
        try {
            // Save main report
            $reportData = [
                'report_name' => $data['report_name'],
                'base_table' => $data['base_table'],
                'description' => $data['description'],
                'is_template' => $data['is_template'] ?? 0
            ];
            
            if (isset($data['id']) && $data['id']) {
                $this->update($data['id'], $reportData);
                $reportId = $data['id'];
                
                // Delete existing related data
                $this->deleteEnhancedReportRelations($reportId);
            } else {
                $reportId = $this->insert($reportData);
            }
            
            // Save enhanced components
            if (!empty($data['columns'])) {
                $this->saveEnhancedColumns($reportId, $data['columns']);
            }
            
            if (!empty($data['joins'])) {
                $this->saveEnhancedJoins($reportId, $data['joins']);
            }
            
            if (!empty($data['conditions'])) {
                $this->saveEnhancedConditions($reportId, $data['conditions']);
            }
            
            if (!empty($data['orders'])) {
                $this->saveEnhancedOrders($reportId, $data['orders']);
            }
            
            if (!empty($data['groups'])) {
                $this->saveEnhancedGroups($reportId, $data['groups']);
            }
            
            if (!empty($data['having'])) {
                $this->saveEnhancedHaving($reportId, $data['having']);
            }
            
            if (!empty($data['case_mappings'])) {
                $this->saveCaseMappings($reportId, $data['case_mappings']);
            }
            
            if (!empty($data['parameters'])) {
                $this->saveParameters($reportId, $data['parameters']);
            }
            
            $db->transComplete();
            
            if ($db->transStatus() === false) {
                throw new \Exception('Failed to save report definition');
            }
            
            return $reportId;
            
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }
    
    private function deleteEnhancedReportRelations($reportId)
    {
        $db = \Config\Database::connect();
        
        $tables = [
            'report_columns',
            'report_joins',
            'report_conditions',
            'report_orders',
            'report_groups',
            'report_having',
            'report_case_mappings',
            'report_parameters'
        ];
        
        foreach ($tables as $table) {
            $db->table($table)->where('report_id', $reportId)->delete();
        }
    }
    
    private function saveEnhancedColumns($reportId, $columns)
    {
        $db = \Config\Database::connect();
        
        foreach ($columns as $column) {
            $columnData = [
                'report_id' => $reportId,
                'column_expression' => $column['column_expression'],
                'alias' => $column['alias'],
                'column_type' => $column['column_type'] ?? 'SELECT',
                'data_type' => $column['data_type'] ?? 'string',
                'column_order' => $column['column_order'] ?? 0,
                'is_visible' => $column['is_visible'] ?? 1,
                'aggregate_function' => $column['aggregate_function'] ?? null,
                'format_pattern' => $column['format_pattern'] ?? null,
                'is_calculated' => $column['is_calculated'] ?? 0,
                'calculation_expression' => $column['calculation_expression'] ?? null
            ];
            $db->table('report_columns')->insert($columnData);
        }
    }
    
    private function saveEnhancedJoins($reportId, $joins)
    {
        $db = \Config\Database::connect();
        
        foreach ($joins as $join) {
            $joinData = [
                'report_id' => $reportId,
                'join_type' => $join['join_type'],
                'table_name' => $join['table_name'],
                'alias' => $join['alias'] ?? null,
                'join_condition' => $join['join_condition'],
                'join_order' => $join['join_order'] ?? 0,
                'is_subquery' => $join['is_subquery'] ?? 0,
                'subquery_sql' => $join['subquery_sql'] ?? null,
                'is_parameterized' => $join['is_parameterized'] ?? 0,
                'parameter_bindings' => isset($join['parameter_bindings']) ? json_encode($join['parameter_bindings']) : null
            ];
            $db->table('report_joins')->insert($joinData);
        }
    }
    
    private function saveEnhancedConditions($reportId, $conditions)
    {
        $db = \Config\Database::connect();
        
        foreach ($conditions as $condition) {
            $conditionData = [
                'report_id' => $reportId,
                'condition_type' => $condition['condition_type'],
                'condition_expression' => $condition['condition_expression'],
                'operator' => $condition['operator'] ?? 'AND',
                'condition_group' => $condition['condition_group'] ?? 0,
                'condition_order' => $condition['condition_order'] ?? 0,
                'is_parameter' => $condition['is_parameter'] ?? 0,
                'parameter_name' => $condition['parameter_name'] ?? null,
                'parameter_default' => $condition['parameter_default'] ?? null,
                'value_type' => $condition['value_type'] ?? 'static',
                'value_source' => $condition['value_source'] ?? null
            ];
            $db->table('report_conditions')->insert($conditionData);
        }
    }
    
    private function saveEnhancedOrders($reportId, $orders)
    {
        $db = \Config\Database::connect();
        
        foreach ($orders as $order) {
            $orderData = [
                'report_id' => $reportId,
                'order_type' => $order['order_type'] ?? 'COLUMN',
                'order_expression' => $order['order_expression'],
                'direction' => $order['direction'] ?? 'ASC',
                'nulls_order' => $order['nulls_order'] ?? null,
                'order_sequence' => $order['order_sequence'] ?? 0,
                'is_parameterized' => $order['is_parameterized'] ?? 0,
                'parameter_name' => $order['parameter_name'] ?? null
            ];
            $db->table('report_orders')->insert($orderData);
        }
    }
    
    private function saveEnhancedGroups($reportId, $groups)
    {
        $db = \Config\Database::connect();
        
        foreach ($groups as $group) {
            $groupData = [
                'report_id' => $reportId,
                'group_type' => $group['group_type'] ?? 'COLUMN',
                'group_expression' => $group['group_expression'],
                'group_order' => $group['group_order'] ?? 0,
                'with_rollup' => $group['with_rollup'] ?? 0,
                'is_complex' => $group['is_complex'] ?? 0
            ];
            $db->table('report_groups')->insert($groupData);
        }
    }
    
    private function saveEnhancedHaving($reportId, $havingConditions)
    {
        $db = \Config\Database::connect();
        
        foreach ($havingConditions as $having) {
            $havingData = [
                'report_id' => $reportId,
                'having_expression' => $having['having_expression'],
                'operator' => $having['operator'] ?? 'AND',
                'having_order' => $having['having_order'] ?? 0,
                'is_parameter' => $having['is_parameter'] ?? 0,
                'parameter_name' => $having['parameter_name'] ?? null
            ];
            $db->table('report_having')->insert($havingData);
        }
    }
    
    private function saveCaseMappings($reportId, $caseMappings)
    {
        $db = \Config\Database::connect();
        
        foreach ($caseMappings as $case) {
            $caseData = [
                'report_id' => $reportId,
                'case_field' => $case['case_field'],
                'when_expression' => $case['when_expression'],
                'then_value' => $case['then_value'],
                'case_order' => $case['case_order'] ?? 0,
                'else_value' => $case['else_value'] ?? null
            ];
            $db->table('report_case_mappings')->insert($caseData);
        }
    }
    
    /**
     * Save complete report definition
     */
    // public function saveReportDefinition($data)
    // {
    //     $db = \Config\Database::connect();
    //     $db->transStart();
        
    //     try {
    //         // Save main report
    //         $reportData = [
    //             'report_name' => $data['report_name'],
    //             'base_table' => $data['base_table'],
    //             'description' => $data['description'],
    //             'is_template' => $data['is_template'] ?? 0
    //         ];
            
    //         if (isset($data['id']) && $data['id']) {
    //             $this->update($data['id'], $reportData);
    //             $reportId = $data['id'];
                
    //             // Delete existing related data
    //             $this->deleteReportRelations($reportId);
    //         } else {
    //             $reportId = $this->insert($reportData);
    //         }
            
    //         // Save joins
    //         if (!empty($data['joins'])) {
    //             $this->saveJoins($reportId, $data['joins']);
    //         }
            
    //         // Save columns
    //         if (!empty($data['columns'])) {
    //             $this->saveColumns($reportId, $data['columns']);
    //         }
            
    //         // Save conditions
    //         if (!empty($data['conditions'])) {
    //             $this->saveConditions($reportId, $data['conditions']);
    //         }
            
    //         // Save groups
    //         if (!empty($data['groups'])) {
    //             $this->saveGroups($reportId, $data['groups']);
    //         }
            
    //         // Save orders
    //         if (!empty($data['orders'])) {
    //             $this->saveOrders($reportId, $data['orders']);
    //         }
            
    //         // Save parameters
    //         if (!empty($data['parameters'])) {
    //             $this->saveParameters($reportId, $data['parameters']);
    //         }
            
    //         $db->transComplete();
            
    //         if ($db->transStatus() === false) {
    //             throw new \Exception('Failed to save report definition');
    //         }
            
    //         return $reportId;
            
    //     } catch (\Exception $e) {
    //         $db->transRollback();
    //         throw $e;
    //     }
    // }

    public function saveReportDefinition($data)
    {
        $db = \Config\Database::connect();
        $db->transStart();
        
        try {
            // Save main report
            $reportData = [
                'report_name' => $data['report_name'] ?? '',
                'base_table' => $data['base_table'] ?? '',
                'description' => $data['description'] ?? '',
                'is_template' => $data['is_template'] ?? 0
            ];
            
            if (isset($data['id']) && $data['id']) {
                $this->update($data['id'], $reportData);
                $reportId = $data['id'];
                
                // Delete existing related data
                $this->deleteReportRelations($reportId);
            } else {
                $reportId = $this->insert($reportData);
            }
            
            // Save joins
            if (!empty($data['joins'])) {
                $this->saveJoins($reportId, $data['joins']);
            }
            
            // Save columns
            if (!empty($data['columns'])) {
                $this->saveColumns($reportId, $data['columns']);
            }
            
            // Save conditions
            if (!empty($data['conditions'])) {
                $this->saveConditions($reportId, $data['conditions']);
            }
            
            // Save groups - FIXED: Use group_expression instead of group_column
            if (!empty($data['groups'])) {
                $this->saveGroups($reportId, $data['groups']);
            }
            
            // Save orders - FIXED: Use order_expression instead of order_column
            if (!empty($data['orders'])) {
                $this->saveOrders($reportId, $data['orders']);
            }
            
            // Save parameters
            if (!empty($data['parameters'])) {
                $this->saveParameters($reportId, $data['parameters']);
            }
            
            $db->transComplete();
            
            if ($db->transStatus() === false) {
                throw new \Exception('Failed to save report definition');
            }
            
            return $reportId;
            
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }
    
    // private function deleteReportRelations($reportId)
    // {
    //     $db = \Config\Database::connect();
        
    //     $tables = [
    //         'report_joins',
    //         'report_columns',
    //         'report_conditions',
    //         'report_groups',
    //         'report_orders',
    //         'report_parameters'
    //     ];
        
    //     foreach ($tables as $table) {
    //         $db->table($table)->where('report_id', $reportId)->delete();
    //     }
    // }

    private function deleteReportRelations($reportId)
    {
        $db = \Config\Database::connect();
        
        $tables = [
            'report_joins',
            'report_columns',
            'report_conditions',
            'report_groups',
            'report_orders',
            'report_parameters'
        ];
        
        foreach ($tables as $table) {
            $db->table($table)->where('report_id', $reportId)->delete();
        }
    }
    
    // private function saveJoins($reportId, $joins)
    // {
    //     $db = \Config\Database::connect();
        
    //     foreach ($joins as $join) {
    //         $joinData = [
    //             'report_id' => $reportId,
    //             'join_type' => $join['join_type'],
    //             'table_name' => $join['table_name'],
    //             'alias' => $join['alias'] ?? null,
    //             'join_condition' => $join['join_condition'],
    //             'join_order' => $join['join_order'] ?? 0,
    //             'is_subquery' => $join['is_subquery'] ?? 0,
    //             'subquery_sql' => $join['subquery_sql'] ?? null
    //         ];
    //         $db->table('report_joins')->insert($joinData);
    //     }
    // }

    private function saveJoins($reportId, $joins)
    {
        $db = \Config\Database::connect();
        
        foreach ($joins as $join) {
            $joinData = [
                'report_id' => $reportId,
                'join_type' => $join['join_type'] ?? 'INNER',
                'table_name' => $join['table_name'] ?? '',
                'alias' => $join['alias'] ?? null,
                'join_condition' => $join['join_condition'] ?? '',
                'join_order' => $join['join_order'] ?? 0
            ];
            $db->table('report_joins')->insert($joinData);
        }
    }
    
    // private function saveColumns($reportId, $columns)
    // {
    //     $db = \Config\Database::connect();
        
    //     foreach ($columns as $column) {
    //         $columnData = [
    //             'report_id' => $reportId,
    //             'column_expression' => $column['column_expression'],
    //             'alias' => $column['alias'],
    //             'column_type' => $column['column_type'] ?? 'SELECT',
    //             'data_type' => $column['data_type'] ?? 'string',
    //             'column_order' => $column['column_order'] ?? 0,
    //             'is_visible' => $column['is_visible'] ?? 1,
    //             'aggregate_function' => $column['aggregate_function'] ?? null
    //         ];
    //         $db->table('report_columns')->insert($columnData);
    //     }
    // }

    private function saveColumns($reportId, $columns)
    {
        $db = \Config\Database::connect();
        
        foreach ($columns as $index => $column) {
            $columnData = [
                'report_id' => $reportId,
                'column_expression' => $column['column_expression'] ?? '',
                'alias' => $column['alias'] ?? 'column_' . $index,
                'column_type' => $column['column_type'] ?? 'SELECT',
                'data_type' => $column['data_type'] ?? 'string',
                'column_order' => $column['column_order'] ?? $index,
                'is_visible' => $column['is_visible'] ?? 1,
                'aggregate_function' => $column['aggregate_function'] ?? null
            ];
            $db->table('report_columns')->insert($columnData);
        }
    }
    
    // private function saveConditions($reportId, $conditions)
    // {
    //     $db = \Config\Database::connect();
        
    //     foreach ($conditions as $condition) {
    //         $conditionData = [
    //             'report_id' => $reportId,
    //             'condition_type' => $condition['condition_type'],
    //             'condition_expression' => $condition['condition_expression'],
    //             'operator' => $condition['operator'] ?? 'AND',
    //             'condition_group' => $condition['condition_group'] ?? 0,
    //             'condition_order' => $condition['condition_order'] ?? 0,
    //             'is_parameter' => $condition['is_parameter'] ?? 0,
    //             'parameter_name' => $condition['parameter_name'] ?? null,
    //             'parameter_default' => $condition['parameter_default'] ?? null
    //         ];
    //         $db->table('report_conditions')->insert($conditionData);
    //     }
    // }

    private function saveConditions($reportId, $conditions)
    {
        $db = \Config\Database::connect();
        
        foreach ($conditions as $index => $condition) {
            $conditionData = [
                'report_id' => $reportId,
                'condition_type' => $condition['condition_type'] ?? 'WHERE',
                'condition_expression' => $condition['condition_expression'] ?? '',
                'operator' => $condition['operator'] ?? 'AND',
                'condition_group' => $condition['condition_group'] ?? 0,
                'condition_order' => $condition['condition_order'] ?? $index,
                'is_parameter' => $condition['is_parameter'] ?? 0,
                'parameter_name' => $condition['parameter_name'] ?? null,
                'parameter_default' => $condition['parameter_default'] ?? null
            ];
            $db->table('report_conditions')->insert($conditionData);
        }
    }
    
    // private function saveGroups($reportId, $groups)
    // {
    //     $db = \Config\Database::connect();
        
    //     foreach ($groups as $group) {
    //         $groupData = [
    //             'report_id' => $reportId,
    //             'group_column' => $group['group_column'],
    //             'group_order' => $group['group_order'] ?? 0
    //         ];
    //         $db->table('report_groups')->insert($groupData);
    //     }
    // }

    private function saveGroups($reportId, $groups)
    {
        $db = \Config\Database::connect();
        
        foreach ($groups as $index => $group) {
            $groupData = [
                'report_id' => $reportId,
                'group_expression' => $group['group_expression'] ?? '', // FIXED: Use group_expression
                'group_order' => $group['group_order'] ?? $index
            ];
            $db->table('report_groups')->insert($groupData);
        }
    }
    
    // private function saveOrders($reportId, $orders)
    // {
    //     $db = \Config\Database::connect();
        
    //     foreach ($orders as $order) {
    //         $orderData = [
    //             'report_id' => $reportId,
    //             'order_column' => $order['order_column'],
    //             'direction' => $order['direction'] ?? 'ASC',
    //             'order_sequence' => $order['order_sequence'] ?? 0
    //         ];
    //         $db->table('report_orders')->insert($orderData);
    //     }
    // }

    private function saveOrders($reportId, $orders)
    {
        $db = \Config\Database::connect();
        
        foreach ($orders as $index => $order) {
            $orderData = [
                'report_id' => $reportId,
                'order_expression' => $order['order_expression'] ?? '', // FIXED: Use order_expression
                'direction' => $order['direction'] ?? 'ASC',
                'order_sequence' => $order['order_sequence'] ?? $index
            ];
            $db->table('report_orders')->insert($orderData);
        }
    }
    
    // private function saveParameters($reportId, $parameters)
    // {
    //     $db = \Config\Database::connect();
        
    //     foreach ($parameters as $parameter) {
    //         $parameterData = [
    //             'report_id' => $reportId,
    //             'parameter_name' => $parameter['parameter_name'],
    //             'parameter_label' => $parameter['parameter_label'],
    //             'data_type' => $parameter['data_type'] ?? 'string',
    //             'input_type' => $parameter['input_type'] ?? 'text',
    //             'default_value' => $parameter['default_value'] ?? null,
    //             'required' => $parameter['required'] ?? 0,
    //             'parameter_order' => $parameter['parameter_order'] ?? 0,
    //             'options_query' => $parameter['options_query'] ?? null
    //         ];
    //         $db->table('report_parameters')->insert($parameterData);
    //     }
    // }

    private function saveParameters($reportId, $parameters)
    {
        $db = \Config\Database::connect();
        
        foreach ($parameters as $parameter) {
            $parameterData = [
                'report_id' => $reportId,
                'parameter_name' => $parameter['parameter_name'] ?? '',
                'parameter_label' => $parameter['parameter_label'] ?? '',
                'data_type' => $parameter['data_type'] ?? 'string',
                'input_type' => $parameter['input_type'] ?? 'text',
                'default_value' => $parameter['default_value'] ?? null,
                'required' => $parameter['required'] ?? 0,
                'parameter_order' => $parameter['parameter_order'] ?? 0,
                'options_query' => $parameter['options_query'] ?? null
            ];
            $db->table('report_parameters')->insert($parameterData);
        }
    }
    
    /**
     * Get all database tables for UI
     */
    public function getDatabaseTables()
    {
        $db = \Config\Database::connect();
        return $db->listTables();
    }
    
    /**
     * Get table columns for UI
     */
    public function getTableColumns($tableName)
    {
        $db = \Config\Database::connect();
        return $db->getFieldData($tableName);
    }
}