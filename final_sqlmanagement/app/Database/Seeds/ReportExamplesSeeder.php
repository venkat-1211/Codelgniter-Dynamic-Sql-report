<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class ReportExamplesSeeder extends Seeder
{
    public function run()
    {
        // Report 1: Candidate Licensing Status
        $reportId1 = $this->createReport([
            'report_name' => 'Candidate Licensing Status',
            'base_table' => 'candidates',
            'description' => 'Report showing candidate licensing information with complex CASE WHEN expressions',
            'is_template' => 1,
            'created_at' => Time::now()
        ]);

        $this->createReportColumns($reportId1, [
            ['column_expression' => 'ha.candidate_id', 'alias' => 'candidate_id', 'column_order' => 1],
            ['column_expression' => "CONCAT(UPPER(can.last_name),' ',can.first_name)", 'alias' => 'candidate_name', 'column_order' => 2],
            ['column_expression' => 'ofl.location', 'alias' => 'office_location', 'column_order' => 3],
            ['column_expression' => "CASE WHEN cq.file_name IS NOT NULL AND cq.file_name!='' THEN 'Yes' ELSE 'No' END", 'alias' => 'have_license', 'column_order' => 4],
            ['column_expression' => "CASE WHEN cq.file_name IS NOT NULL AND cq.file_name!='' THEN cq.expire_date ELSE '' END", 'alias' => 'expire_date', 'column_order' => 5],
            ['column_expression' => "CASE LOWER(cad.active_candidate) WHEN 'yes' THEN IF(cad.onhold_status = 1, 'Active/On Hold', 'Active') WHEN 'fld' THEN 'Filled' END", 'alias' => 'candidate_status', 'column_order' => 6]
        ]);

        $this->createReportJoins($reportId1, [
            ['join_type' => 'JOIN', 'table_name' => 'candidates_additional', 'alias' => 'cad', 'join_condition' => 'can.id=cad.candidate_id', 'join_order' => 1],
            ['join_type' => 'JOIN', 'table_name' => 'home_application', 'alias' => 'ha', 'join_condition' => 'can.id=ha.candidate_id', 'join_order' => 2],
            ['join_type' => 'JOIN', 'table_name' => 'candidates_tags', 'alias' => 'ct', 'join_condition' => 'can.id=ct.candidate_id', 'join_order' => 3],
            ['join_type' => 'JOIN', 'table_name' => 'office_locations', 'alias' => 'ofl', 'join_condition' => 'ofl.id=can.registration_office', 'join_order' => 4],
            ['join_type' => 'LEFT', 'table_name' => 'candidates_qualification', 'alias' => 'cq', 'join_condition' => 'can.id=cq.candidate_id', 'join_order' => 5]
        ]);

        $this->createReportConditions($reportId1, [
            ['condition_type' => 'WHERE', 'condition_expression' => 'can.registration_office IN (?,?,?)', 'operator' => 'AND', 'is_parameter' => 1, 'parameter_name' => 'office_ids'],
            ['condition_type' => 'WHERE', 'condition_expression' => "LOWER(cad.active_candidate) IN ('yes')", 'operator' => 'AND'],
            ['condition_type' => 'WHERE', 'condition_expression' => "ha.skills LIKE ?", 'operator' => 'AND', 'is_parameter' => 1, 'parameter_name' => 'skill_search']
        ]);

        $this->createReportGroups($reportId1, [
            ['group_column' => 'can.id', 'group_order' => 1]
        ]);

        $this->createReportOrders($reportId1, [
            ['order_column' => 'can.last_name', 'direction' => 'ASC', 'order_sequence' => 1]
        ]);

        // Report 2: Total Worked Hours
        $reportId2 = $this->createReport([
            'report_name' => 'Total Worked Hours by Agency',
            'base_table' => 'joborders',
            'description' => 'Calculate total hours worked with TIMESTAMPDIFF and ROUND functions',
            'is_template' => 1,
            'created_at' => Time::now()
        ]);

        $this->createReportColumns($reportId2, [
            ['column_expression' => 'jo.candidate_id', 'alias' => 'candidate_id', 'column_order' => 1],
            ['column_expression' => "CONCAT(UPPER(can.last_name),' ',can.first_name)", 'alias' => 'candidate_name', 'column_order' => 2],
            ['column_expression' => "CONCAT_WS(' - ',cl.client_name,si.site_name,ag.agency_name)", 'alias' => 'client_site_agency', 'column_order' => 3],
            ['column_expression' => "ROUND(SUM(TIMESTAMPDIFF(MINUTE, ts.start_date_time, ts.end_date_time)) / 60, 2)", 'alias' => 'total_hours', 'column_order' => 4, 'aggregate_function' => 'SUM']
        ]);

        $this->createReportJoins($reportId2, [
            ['join_type' => 'JOIN', 'table_name' => 'timesheets', 'alias' => 'ts', 'join_condition' => 'jo.id=ts.job_id', 'join_order' => 1],
            ['join_type' => 'JOIN', 'table_name' => 'candidates', 'alias' => 'can', 'join_condition' => 'can.id=jo.candidate_id', 'join_order' => 2],
            ['join_type' => 'JOIN', 'table_name' => 'client', 'alias' => 'cl', 'join_condition' => 'cl.id=jo.client_id', 'join_order' => 3],
            ['join_type' => 'JOIN', 'table_name' => 'site', 'alias' => 'si', 'join_condition' => 'si.id=jo.site_id', 'join_order' => 4],
            ['join_type' => 'JOIN', 'table_name' => 'agency', 'alias' => 'ag', 'join_condition' => 'ag.id=jo.agency_id', 'join_order' => 5]
        ]);

        $this->createReportConditions($reportId2, [
            ['condition_type' => 'WHERE', 'condition_expression' => 'jo.agency_id = ?', 'operator' => 'AND', 'is_parameter' => 1, 'parameter_name' => 'agency_id'],
            ['condition_type' => 'WHERE', 'condition_expression' => "LOWER(jo.job_status) IN ('filled','closed')", 'operator' => 'AND']
        ]);

        $this->createReportGroups($reportId2, [
            ['group_column' => 'can.id', 'group_order' => 1]
        ]);

        // Report 6: Complex Case Mappings (Simplified version)
        $reportId6 = $this->createReport([
            'report_name' => 'Ashley Services - Complex Candidate Report',
            'base_table' => 'joborders',
            'description' => 'Extremely complex report with nested CASE WHEN, subqueries, and multiple mappings',
            'is_template' => 1,
            'created_at' => Time::now()
        ]);

        // Create an even more complex report as bonus
        $bonusReportId = $this->createReport([
            'report_name' => 'Advanced Candidate Analytics',
            'base_table' => 'candidates',
            'description' => 'Bonus: Most complex report with multiple subqueries, window functions, and dynamic calculations',
            'is_template' => 1,
            'created_at' => Time::now()
        ]);

        // Add parameters to bonus report
        $this->db->table('report_parameters')->insert([
            'report_id' => $bonusReportId,
            'parameter_name' => 'start_date',
            'parameter_label' => 'Start Date',
            'data_type' => 'date',
            'input_type' => 'date',
            'required' => 1,
            'parameter_order' => 1
        ]);

        $this->db->table('report_parameters')->insert([
            'report_id' => $bonusReportId,
            'parameter_name' => 'end_date',
            'parameter_label' => 'End Date',
            'data_type' => 'date',
            'input_type' => 'date',
            'required' => 1,
            'parameter_order' => 2
        ]);
    }

    private function createReport($data)
    {
        $this->db->table('reports')->insert($data);
        return $this->db->insertID();
    }

    private function createReportColumns($reportId, $columns)
    {
        foreach ($columns as $column) {
            $column['report_id'] = $reportId;
            $this->db->table('report_columns')->insert($column);
        }
    }

    private function createReportJoins($reportId, $joins)
    {
        foreach ($joins as $join) {
            $join['report_id'] = $reportId;
            $this->db->table('report_joins')->insert($join);
        }
    }

    private function createReportConditions($reportId, $conditions)
    {
        foreach ($conditions as $condition) {
            $condition['report_id'] = $reportId;
            $this->db->table('report_conditions')->insert($condition);
        }
    }

    private function createReportGroups($reportId, $groups)
    {
        foreach ($groups as $group) {
            $group['report_id'] = $reportId;
            $this->db->table('report_groups')->insert($group);
        }
    }

    private function createReportOrders($reportId, $orders)
    {
        foreach ($orders as $order) {
            $order['report_id'] = $reportId;
            $this->db->table('report_orders')->insert($order);
        }
    }
}