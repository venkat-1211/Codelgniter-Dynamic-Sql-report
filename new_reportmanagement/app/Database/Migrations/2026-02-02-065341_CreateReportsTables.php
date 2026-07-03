<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReportsTables extends Migration
{
    public function up()
    {
        // reports table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'report_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'base_table' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
                'comment'    => 'Main table for the report',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'is_template' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => 'Whether this is a reusable template',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('is_active');
        $this->forge->addKey('is_template');
        $this->forge->createTable('reports');

        // report_joins table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'report_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'join_type' => [
                'type'       => 'ENUM',
                'constraint' => ['INNER', 'LEFT', 'RIGHT', 'FULL'],
                'default'    => 'INNER',
            ],
            'table_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'table_alias' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'join_condition' => [
                'type' => 'TEXT',
                'null' => false,
                'comment' => 'SQL join condition, can use {alias} placeholders',
            ],
            'join_order' => [
                'type'       => 'INT',
                'constraint' => 3,
                'default'    => 0,
                'comment'    => 'Order in which joins are applied',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('report_id');
        $this->forge->addKey('join_order');
        $this->forge->addForeignKey('report_id', 'reports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('report_joins');

        // report_columns table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'report_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'column_expression' => [
                'type' => 'TEXT',
                'null' => false,
                'comment' => 'SQL expression for the column (can be complex: CASE, functions, etc.)',
            ],
            'alias' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'data_type' => [
                'type'       => 'ENUM',
                'constraint' => ['string', 'integer', 'decimal', 'date', 'datetime', 'boolean'],
                'default'    => 'string',
            ],
            'is_groupable' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => 'Whether this column can be used in GROUP BY',
            ],
            'is_sortable' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'comment'    => 'Whether this column can be sorted',
            ],
            'is_filterable' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => 'Whether this column can be filtered',
            ],
            'display_order' => [
                'type'       => 'INT',
                'constraint' => 3,
                'default'    => 0,
            ],
            'format_pattern' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'Format pattern (e.g., "currency", "date:Y-m-d")',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['report_id', 'display_order']);
        $this->forge->addForeignKey('report_id', 'reports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('report_columns');

        // report_filters table (was report_conditions)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'report_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'condition_type' => [
                'type'       => 'ENUM',
                'constraint' => ['WHERE', 'HAVING', 'EXISTS', 'NOT EXISTS'],
                'default'    => 'WHERE',
            ],
            'condition_expression' => [
                'type' => 'TEXT',
                'null' => false,
                'comment' => 'SQL condition expression with parameter placeholders',
            ],
            'parameter_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'Name of parameter for dynamic filtering',
            ],
            'parameter_type' => [
                'type'       => 'ENUM',
                'constraint' => ['string', 'integer', 'decimal', 'date', 'datetime', 'boolean', 'array'],
                'null'       => true,
            ],
            'default_value' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'is_required' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'filter_order' => [
                'type'       => 'INT',
                'constraint' => 3,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['report_id', 'condition_type', 'filter_order']);
        $this->forge->addForeignKey('report_id', 'reports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('report_filters');

        // report_groups table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'report_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'column_alias' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
                'comment'    => 'Alias of column to group by',
            ],
            'group_order' => [
                'type'       => 'INT',
                'constraint' => 3,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['report_id', 'group_order']);
        $this->forge->addForeignKey('report_id', 'reports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('report_groups');

        // report_orders table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'report_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'column_alias' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'direction' => [
                'type'       => 'ENUM',
                'constraint' => ['ASC', 'DESC'],
                'default'    => 'ASC',
            ],
            'order_priority' => [
                'type'       => 'INT',
                'constraint' => 3,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['report_id', 'order_priority']);
        $this->forge->addForeignKey('report_id', 'reports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('report_orders');

        // report_parameters table for runtime filters
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'report_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'parameter_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'parameter_type' => [
                'type'       => 'ENUM',
                'constraint' => ['string', 'integer', 'decimal', 'date', 'datetime', 'boolean', 'array'],
                'default'    => 'string',
            ],
            'parameter_label' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'input_type' => [
                'type'       => 'ENUM',
                'constraint' => ['text', 'number', 'date', 'datetime-local', 'select', 'multiselect', 'checkbox'],
                'default'    => 'text',
            ],
            'options_query' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'SQL query to populate select options',
            ],
            'is_required' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['report_id', 'parameter_key']);
        $this->forge->addForeignKey('report_id', 'reports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('report_parameters');
    }

    public function down()
    {
        $this->forge->dropTable('report_parameters');
        $this->forge->dropTable('report_orders');
        $this->forge->dropTable('report_groups');
        $this->forge->dropTable('report_filters');
        $this->forge->dropTable('report_columns');
        $this->forge->dropTable('report_joins');
        $this->forge->dropTable('reports');
    }
}