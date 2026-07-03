<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReportTables extends Migration
{
    public function up()
    {
        // Table: reports
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'report_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'base_table' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'is_template' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('is_template');
        $this->forge->createTable('reports');

        // Table: report_joins
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
            'join_type' => [
                'type' => 'ENUM',
                'constraint' => ['INNER', 'LEFT', 'RIGHT', 'FULL OUTER', 'CROSS'],
                'default' => 'INNER'
            ],
            'table_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'alias' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true
            ],
            'join_condition' => [
                'type' => 'TEXT',
                'null' => false
            ],
            'join_order' => [
                'type' => 'INT',
                'constraint' => 3,
                'default' => 0
            ],
            'is_subquery' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0
            ],
            'subquery_sql' => [
                'type' => 'TEXT',
                'null' => true
            ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('report_id');
        $this->forge->addForeignKey('report_id', 'reports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('report_joins');

        // Table: report_columns
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
            'column_expression' => [
                'type' => 'TEXT',
                'null' => false
            ],
            'alias' => [
                'type' => 'VARCHAR',
                'constraint' => 100
            ],
            'column_type' => [
                'type' => 'ENUM',
                'constraint' => ['SELECT', 'CALCULATED', 'CONDITIONAL'],
                'default' => 'SELECT'
            ],
            'data_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'string'
            ],
            'column_order' => [
                'type' => 'INT',
                'constraint' => 3,
                'default' => 0
            ],
            'is_visible' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1
            ],
            'aggregate_function' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true
            ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('report_id');
        $this->forge->addForeignKey('report_id', 'reports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('report_columns');

        // Table: report_conditions
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
            'condition_type' => [
                'type' => 'ENUM',
                'constraint' => ['WHERE', 'HAVING', 'EXISTS', 'NOT EXISTS', 'IN', 'NOT IN'],
                'default' => 'WHERE'
            ],
            'condition_expression' => [
                'type' => 'TEXT',
                'null' => false
            ],
            'operator' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'default' => 'AND'
            ],
            'condition_group' => [
                'type' => 'INT',
                'constraint' => 2,
                'default' => 0
            ],
            'condition_order' => [
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
            ],
            'parameter_default' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('report_id');
        $this->forge->addForeignKey('report_id', 'reports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('report_conditions');

        // Table: report_groups
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
            'group_column' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'group_order' => [
                'type' => 'INT',
                'constraint' => 3,
                'default' => 0
            ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('report_id');
        $this->forge->addForeignKey('report_id', 'reports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('report_groups');

        // Table: report_orders
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
            'order_column' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'direction' => [
                'type' => 'ENUM',
                'constraint' => ['ASC', 'DESC'],
                'default' => 'ASC'
            ],
            'order_sequence' => [
                'type' => 'INT',
                'constraint' => 3,
                'default' => 0
            ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('report_id');
        $this->forge->addForeignKey('report_id', 'reports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('report_orders');

        // Table: report_parameters
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
            'parameter_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100
            ],
            'parameter_label' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'data_type' => [
                'type' => 'ENUM',
                'constraint' => ['string', 'integer', 'date', 'datetime', 'boolean', 'array'],
                'default' => 'string'
            ],
            'input_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'text'
            ],
            'default_value' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ],
            'required' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0
            ],
            'parameter_order' => [
                'type' => 'INT',
                'constraint' => 3,
                'default' => 0
            ],
            'options_query' => [
                'type' => 'TEXT',
                'null' => true
            ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('report_id');
        $this->forge->addForeignKey('report_id', 'reports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('report_parameters');
    }

    public function down()
    {
        $this->forge->dropTable('report_parameters', true);
        $this->forge->dropTable('report_orders', true);
        $this->forge->dropTable('report_groups', true);
        $this->forge->dropTable('report_conditions', true);
        $this->forge->dropTable('report_columns', true);
        $this->forge->dropTable('report_joins', true);
        $this->forge->dropTable('reports', true);
    }
}