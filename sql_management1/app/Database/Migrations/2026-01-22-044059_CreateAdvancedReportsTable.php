<?php
// app/Database/Migrations/2024_01_21_000002_create_advanced_reports_tables.php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdvancedReportsTables extends Migration
{
    public function up()
    {
        // Advanced reports table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'report_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'report_type' => [
                'type' => 'ENUM',
                'constraint' => ['simple', 'advanced', 'custom_sql'],
                'default' => 'simple',
            ],
            'base_tables' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON array of base tables'
            ],
            'joins_config' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON array of join configurations'
            ],
            'columns_config' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON array of column configurations'
            ],
            'calculated_fields' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON array of calculated fields'
            ],
            'filters_config' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON array of filter configurations'
            ],
            'grouping_config' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON array of grouping configurations'
            ],
            'sorting_config' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON array of sorting configurations'
            ],
            'subqueries_config' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON array of subquery configurations'
            ],
            'custom_sql' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'comment' => 'Custom SQL for advanced reports'
            ],
            'access_roles' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON array of roles'
            ],
            'export_formats' => [
                'type' => 'TEXT',
                'default' => '["xlsx","csv"]',
                'comment' => 'JSON array of formats'
            ],
            'created_by' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->createTable('advanced_reports');

        // Report templates for complex queries
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'template_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'sql_template' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'parameters' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON array of parameters'
            ],
            'created_by' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->createTable('report_templates');
    }

    public function down()
    {
        $this->forge->dropTable('report_templates');
        $this->forge->dropTable('advanced_reports');
    }
}