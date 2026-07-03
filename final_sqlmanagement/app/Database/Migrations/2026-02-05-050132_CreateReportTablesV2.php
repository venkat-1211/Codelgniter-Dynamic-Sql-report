<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReportTablesV2 extends Migration
{
    public function up()
    {
        // Drop existing tables if they exist (for update)
        $this->forge->dropTable('report_orders', true);
        $this->forge->dropTable('report_groups', true);
        $this->forge->dropTable('report_having', true);
        
        // Create enhanced report_orders table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'report_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true
            ],
            'order_type' => [
                'type' => 'ENUM',
                'constraint' => ['COLUMN', 'EXPRESSION', 'CASE', 'FUNCTION'],
                'default' => 'COLUMN'
            ],
            'order_expression' => [
                'type' => 'TEXT',
                'null' => false
            ],
            'direction' => [
                'type' => 'ENUM',
                'constraint' => ['ASC', 'DESC'],
                'default' => 'ASC'
            ],
            'nulls_order' => [
                'type' => 'ENUM',
                'constraint' => ['NULLS FIRST', 'NULLS LAST'],
                'null' => true
            ],
            'order_sequence' => [
                'type' => 'INT',
                'constraint' => 3,
                'default' => 0
            ],
            'is_parameterized' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0
            ],
            'parameter_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true
            ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('report_id');
        $this->forge->addForeignKey('report_id', 'reports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('report_orders');

        // Create enhanced report_groups table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'report_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true
            ],
            'group_type' => [
                'type' => 'ENUM',
                'constraint' => ['COLUMN', 'EXPRESSION', 'ROLLUP', 'CUBE', 'GROUPING_SETS'],
                'default' => 'COLUMN'
            ],
            'group_expression' => [
                'type' => 'TEXT',
                'null' => false
            ],
            'group_order' => [
                'type' => 'INT',
                'constraint' => 3,
                'default' => 0
            ],
            'with_rollup' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0
            ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('report_id');
        $this->forge->addForeignKey('report_id', 'reports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('report_groups');

        // Create report_having table for complex HAVING conditions
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'report_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true
            ],
            'having_expression' => [
                'type' => 'TEXT',
                'null' => false
            ],
            'operator' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'default' => 'AND'
            ],
            'having_order' => [
                'type' => 'INT',
                'constraint' => 3,
                'default' => 0
            ],
            'is_parameter' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0
            ],
            'parameter_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true
            ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('report_id');
        $this->forge->addForeignKey('report_id', 'reports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('report_having');

        // Create report_case_mappings for complex CASE WHEN
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'report_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true
            ],
            'case_field' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'when_expression' => [
                'type' => 'TEXT'
            ],
            'then_value' => [
                'type' => 'TEXT'
            ],
            'case_order' => [
                'type' => 'INT',
                'constraint' => 3,
                'default' => 0
            ],
            'else_value' => [
                'type' => 'TEXT',
                'null' => true
            ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('report_id');
        $this->forge->addForeignKey('report_id', 'reports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('report_case_mappings');
    }

    public function down()
    {
        $this->forge->dropTable('report_case_mappings', true);
        $this->forge->dropTable('report_having', true);
        $this->forge->dropTable('report_groups', true);
        $this->forge->dropTable('report_orders', true);
    }
}