<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/hint/show-hint.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .builder-container {
            min-height: calc(100vh - 200px);
        }
        .section-card {
            margin-bottom: 20px;
            border-left: 4px solid #0d6efd;
            transition: all 0.3s;
        }
        .section-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .sql-preview {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            border-radius: 5px;
            max-height: 300px;
            overflow-y: auto;
            font-size: 0.9em;
        }
        .draggable-item {
            cursor: move;
            padding: 10px;
            margin: 5px 0;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            transition: all 0.2s;
        }
        .draggable-item:hover {
            background: #f8f9fa;
            border-color: #0d6efd;
        }
        .CodeMirror {
            height: auto;
            min-height: 100px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        .table-list {
            max-height: 300px;
            overflow-y: auto;
        }
        .section-icon {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 10px;
        }
        .accordion-button:not(.collapsed) {
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }
        .remove-btn {
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        .remove-btn:hover {
            opacity: 1;
        }
        .sortable-ghost {
            opacity: 0.4;
            background: #c8ebfb;
        }
        .complex-section {
            border-left: 4px solid #20c997;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= base_url('reports') ?>">
                <i class="fas fa-arrow-left"></i> Report Builder
            </a>
            <div class="navbar-text">
                <span class="badge bg-info" id="reportStatus">
                    <?= isset($report['id']) ? 'Editing Report' : 'New Report' ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4 builder-container">
        <form id="reportForm" data-report-id="<?= $report['id'] ?? '' ?>">
            <input type="hidden" name="id" value="<?= $report['id'] ?? '' ?>">
            
            <div class="row">
                <!-- Left Panel: Report Structure -->
                <div class="col-md-8">
                    <!-- Report Information -->
                    <div class="card section-card">
                        <div class="card-header bg-primary text-white d-flex align-items-center">
                            <div class="section-icon bg-white text-primary">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <h5 class="mb-0">Report Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Report Name *</label>
                                        <input type="text" class="form-control" name="report_name" 
                                               value="<?= htmlspecialchars($report['report_name'] ?? '') ?>" 
                                               required placeholder="Enter report name">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Base Table *</label>
                                        <select class="form-control select2-table" name="base_table" id="baseTable" required>
                                            <option value="">Select a table</option>
                                            <?php foreach ($tables as $table): ?>
                                                <option value="<?= $table ?>" 
                                                    <?= ($report['base_table'] ?? '') == $table ? 'selected' : '' ?>>
                                                    <?= $table ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="2" 
                                          placeholder="Describe what this report does"><?= htmlspecialchars($report['description'] ?? '') ?></textarea>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_template" value="1" 
                                       id="isTemplate" <?= ($report['is_template'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isTemplate">Save as template</label>
                            </div>
                        </div>
                    </div>

                    <!-- Columns Section -->
                    <div class="card section-card">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="section-icon bg-white text-info">
                                    <i class="fas fa-columns"></i>
                                </div>
                                <h5 class="mb-0">Columns</h5>
                                <span class="badge bg-light text-dark ms-2" id="columnCount">0</span>
                            </div>
                            <div>
                                <button type="button" class="btn btn-light btn-sm me-2" onclick="loadTableColumns()">
                                    <i class="fas fa-database"></i> Load Columns
                                </button>
                                <button type="button" class="btn btn-light btn-sm" onclick="addColumn()">
                                    <i class="fas fa-plus"></i> Add Column
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info d-flex align-items-center">
                                <i class="fas fa-info-circle me-2"></i>
                                <small>Drag columns to reorder. Use SQL expressions for calculated fields.</small>
                            </div>
                            <div id="columnsContainer" class="sortable-container">
                                <?php if (!empty($report['columns'])): ?>
                                    <?php foreach ($report['columns'] as $index => $column): ?>
                                        <div class="draggable-item column-item" data-index="<?= $index ?>">
                                            <div class="row g-2">
                                                <div class="col-md-1 text-center pt-2">
                                                    <i class="fas fa-grip-vertical text-muted"></i>
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="text" class="form-control form-control-sm" 
                                                           name="columns[<?= $index ?>][column_expression]"
                                                           value="<?= htmlspecialchars($column['column_expression']) ?>"
                                                           placeholder="Expression (e.g., table.column, CONCAT(...))">
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="text" class="form-control form-control-sm" 
                                                           name="columns[<?= $index ?>][alias]"
                                                           value="<?= htmlspecialchars($column['alias']) ?>"
                                                           placeholder="Alias">
                                                </div>
                                                <div class="col-md-2">
                                                    <select class="form-select form-select-sm" 
                                                            name="columns[<?= $index ?>][aggregate_function]">
                                                        <option value="">No Aggregate</option>
                                                        <option value="SUM" <?= $column['aggregate_function'] == 'SUM' ? 'selected' : '' ?>>SUM</option>
                                                        <option value="COUNT" <?= $column['aggregate_function'] == 'COUNT' ? 'selected' : '' ?>>COUNT</option>
                                                        <option value="AVG" <?= $column['aggregate_function'] == 'AVG' ? 'selected' : '' ?>>AVG</option>
                                                        <option value="MIN" <?= $column['aggregate_function'] == 'MIN' ? 'selected' : '' ?>>MIN</option>
                                                        <option value="MAX" <?= $column['aggregate_function'] == 'MAX' ? 'selected' : '' ?>>MAX</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <select class="form-select form-select-sm" 
                                                            name="columns[<?= $index ?>][data_type]">
                                                        <option value="string" <?= $column['data_type'] == 'string' ? 'selected' : '' ?>>String</option>
                                                        <option value="number" <?= $column['data_type'] == 'number' ? 'selected' : '' ?>>Number</option>
                                                        <option value="date" <?= $column['data_type'] == 'date' ? 'selected' : '' ?>>Date</option>
                                                        <option value="datetime" <?= $column['data_type'] == 'datetime' ? 'selected' : '' ?>>DateTime</option>
                                                        <option value="boolean" <?= $column['data_type'] == 'boolean' ? 'selected' : '' ?>>Boolean</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-1">
                                                    <button type="button" class="btn btn-danger btn-sm w-100 remove-btn" onclick="removeColumn(this)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="text-center mt-3">
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="addColumn()">
                                    <i class="fas fa-plus-circle"></i> Add Another Column
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- JOINS Section -->
                    <div class="card section-card">
                        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="section-icon bg-white text-warning">
                                    <i class="fas fa-link"></i>
                                </div>
                                <h5 class="mb-0">JOINS</h5>
                                <span class="badge bg-light text-dark ms-2" id="joinCount">0</span>
                            </div>
                            <button type="button" class="btn btn-light btn-sm" onclick="addJoin()">
                                <i class="fas fa-plus"></i> Add JOIN
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="joinsContainer" class="sortable-container">
                                <?php if (!empty($report['joins'])): ?>
                                    <?php foreach ($report['joins'] as $index => $join): ?>
                                        <div class="draggable-item join-item" data-index="<?= $index ?>">
                                            <div class="row g-2">
                                                <div class="col-md-1 text-center pt-2">
                                                    <i class="fas fa-grip-vertical text-muted"></i>
                                                </div>
                                                <div class="col-md-2">
                                                    <select class="form-select form-select-sm" 
                                                            name="joins[<?= $index ?>][join_type]">
                                                        <option value="INNER" <?= $join['join_type'] == 'INNER' ? 'selected' : '' ?>>INNER</option>
                                                        <option value="LEFT" <?= $join['join_type'] == 'LEFT' ? 'selected' : '' ?>>LEFT</option>
                                                        <option value="RIGHT" <?= $join['join_type'] == 'RIGHT' ? 'selected' : '' ?>>RIGHT</option>
                                                        <option value="FULL OUTER" <?= $join['join_type'] == 'FULL OUTER' ? 'selected' : '' ?>>FULL OUTER</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <select class="form-select form-select-sm join-table" 
                                                            name="joins[<?= $index ?>][table_name]">
                                                        <option value="">Select table</option>
                                                        <?php foreach ($tables as $table): ?>
                                                            <option value="<?= $table ?>" 
                                                                <?= $join['table_name'] == $table ? 'selected' : '' ?>>
                                                                <?= $table ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="text" class="form-control form-control-sm" 
                                                           name="joins[<?= $index ?>][alias]"
                                                           value="<?= htmlspecialchars($join['alias'] ?? '') ?>"
                                                           placeholder="Alias">
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="text" class="form-control form-control-sm" 
                                                           name="joins[<?= $index ?>][join_condition]"
                                                           value="<?= htmlspecialchars($join['join_condition']) ?>"
                                                           placeholder="Condition (e.g., t1.id = t2.foreign_id)">
                                                </div>
                                                <div class="col-md-1">
                                                    <button type="button" class="btn btn-danger btn-sm w-100 remove-btn" onclick="removeJoin(this)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="text-center mt-3">
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="addJoin()">
                                    <i class="fas fa-plus-circle"></i> Add Another JOIN
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- WHERE Conditions Section -->
                    <div class="card section-card">
                        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="section-icon bg-white text-danger">
                                    <i class="fas fa-filter"></i>
                                </div>
                                <h5 class="mb-0">WHERE Conditions</h5>
                                <span class="badge bg-light text-dark ms-2" id="conditionCount">0</span>
                            </div>
                            <button type="button" class="btn btn-light btn-sm" onclick="addCondition()">
                                <i class="fas fa-plus"></i> Add Condition
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="conditionsContainer">
                                <?php if (!empty($report['conditions'])): ?>
                                    <?php foreach ($report['conditions'] as $index => $condition): ?>
                                        <div class="draggable-item condition-item mb-2" data-index="<?= $index ?>">
                                            <div class="row g-2">
                                                <div class="col-md-1 text-center pt-2">
                                                    <i class="fas fa-grip-vertical text-muted"></i>
                                                </div>
                                                <div class="col-md-3">
                                                    <select class="form-select form-select-sm" 
                                                            name="conditions[<?= $index ?>][condition_type]">
                                                        <option value="WHERE" <?= $condition['condition_type'] == 'WHERE' ? 'selected' : '' ?>>WHERE</option>
                                                        <option value="HAVING" <?= $condition['condition_type'] == 'HAVING' ? 'selected' : '' ?>>HAVING</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" class="form-control" 
                                                               name="conditions[<?= $index ?>][condition_expression]"
                                                               value="<?= htmlspecialchars($condition['condition_expression']) ?>"
                                                               placeholder="Condition (e.g., column = value)">
                                                        <button type="button" class="btn btn-outline-secondary" 
                                                                onclick="showConditionBuilder(this)">
                                                            <i class="fas fa-cogs"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="col-md-1">
                                                    <select class="form-select form-select-sm" 
                                                            name="conditions[<?= $index ?>][operator]">
                                                        <option value="AND" <?= ($condition['operator'] ?? 'AND') == 'AND' ? 'selected' : '' ?>>AND</option>
                                                        <option value="OR" <?= ($condition['operator'] ?? 'AND') == 'OR' ? 'selected' : '' ?>>OR</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-1">
                                                    <button type="button" class="btn btn-danger btn-sm w-100 remove-btn" onclick="removeCondition(this)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="row mt-2">
                                                <div class="col-md-6">
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="checkbox" 
                                                               name="conditions[<?= $index ?>][is_parameter]" value="1"
                                                               <?= $condition['is_parameter'] ? 'checked' : '' ?>
                                                               onchange="toggleParameterFields(this, <?= $index ?>)">
                                                        <label class="form-check-label">Use Parameter</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 parameter-fields-<?= $index ?>" 
                                                     style="display: <?= $condition['is_parameter'] ? 'block' : 'none' ?>;">
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" class="form-control" 
                                                               name="conditions[<?= $index ?>][parameter_name]"
                                                               value="<?= htmlspecialchars($condition['parameter_name'] ?? '') ?>"
                                                               placeholder="Parameter name">
                                                        <input type="text" class="form-control" 
                                                               name="conditions[<?= $index ?>][parameter_default]"
                                                               value="<?= htmlspecialchars($condition['parameter_default'] ?? '') ?>"
                                                               placeholder="Default value">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="text-center mt-3">
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="addCondition()">
                                    <i class="fas fa-plus-circle"></i> Add Another Condition
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- GROUP BY Section (NEW) -->
                    <div class="card section-card complex-section">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="section-icon bg-white text-success">
                                    <i class="fas fa-layer-group"></i>
                                </div>
                                <h5 class="mb-0">GROUP BY</h5>
                                <span class="badge bg-light text-dark ms-2" id="groupCount">0</span>
                            </div>
                            <div>
                                <button type="button" class="btn btn-light btn-sm me-2" onclick="addSimpleGroup()">
                                    <i class="fas fa-plus"></i> Simple
                                </button>
                                <button type="button" class="btn btn-light btn-sm" onclick="addComplexGroup()">
                                    <i class="fas fa-cogs"></i> Advanced
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="groupByAccordion">
                                <!-- Simple GROUP BY -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#simpleGroupBy">
                                            <i class="fas fa-list me-2"></i> Simple Grouping
                                        </button>
                                    </h2>
                                    <div id="simpleGroupBy" class="accordion-collapse collapse" data-bs-parent="#groupByAccordion">
                                        <div class="accordion-body">
                                            <div id="simpleGroupsContainer" class="sortable-container">
                                                <?php if (!empty($report['groups'])): ?>
                                                    <?php foreach ($report['groups'] as $index => $group): ?>
                                                        <?php if (($group['group_type'] ?? 'COLUMN') == 'COLUMN'): ?>
                                                            <div class="draggable-item simple-group-item mb-2" data-index="<?= $index ?>">
                                                                <div class="row g-2">
                                                                    <div class="col-md-1 text-center pt-2">
                                                                        <i class="fas fa-grip-vertical text-muted"></i>
                                                                    </div>
                                                                    <div class="col-md-9">
                                                                        <input type="text" class="form-control form-control-sm" 
                                                                               name="groups[<?= $index ?>][group_expression]"
                                                                               value="<?= htmlspecialchars($group['group_expression']) ?>"
                                                                               placeholder="Column to group by (e.g., department_id, YEAR(created_at))">
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <button type="button" class="btn btn-danger btn-sm w-100 remove-btn" onclick="removeSimpleGroup(this)">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                <input type="hidden" name="groups[<?= $index ?>][group_type]" value="COLUMN">
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-center mt-3">
                                                <button type="button" class="btn btn-outline-success btn-sm" onclick="addSimpleGroup()">
                                                    <i class="fas fa-plus-circle"></i> Add Grouping Column
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Advanced GROUP BY -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#advancedGroupBy">
                                            <i class="fas fa-cogs me-2"></i> Advanced Grouping
                                        </button>
                                    </h2>
                                    <div id="advancedGroupBy" class="accordion-collapse collapse" data-bs-parent="#groupByAccordion">
                                        <div class="accordion-body">
                                            <div id="complexGroupsContainer">
                                                <?php if (!empty($report['groups'])): ?>
                                                    <?php foreach ($report['groups'] as $index => $group): ?>
                                                        <?php if (($group['group_type'] ?? 'COLUMN') != 'COLUMN'): ?>
                                                            <div class="complex-group-item mb-3 p-3 border rounded">
                                                                <div class="row">
                                                                    <div class="col-md-3">
                                                                        <label class="form-label">Group Type</label>
                                                                        <select class="form-control form-control-sm group-type-select" 
                                                                                name="groups[<?= $index ?>][group_type]">
                                                                            <option value="EXPRESSION" <?= $group['group_type'] == 'EXPRESSION' ? 'selected' : '' ?>>Expression</option>
                                                                            <option value="ROLLUP" <?= $group['group_type'] == 'ROLLUP' ? 'selected' : '' ?>>WITH ROLLUP</option>
                                                                            <option value="CUBE" <?= $group['group_type'] == 'CUBE' ? 'selected' : '' ?>>WITH CUBE</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-md-7">
                                                                        <label class="form-label">Expression</label>
                                                                        <textarea class="form-control form-control-sm group-expression" 
                                                                                  name="groups[<?= $index ?>][group_expression]"
                                                                                  rows="2"><?= htmlspecialchars($group['group_expression']) ?></textarea>
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <label class="form-label">Actions</label>
                                                                        <button type="button" class="btn btn-danger btn-sm w-100 remove-btn" onclick="removeComplexGroup(this)">
                                                                            <i class="fas fa-trash"></i> Remove
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                <div class="row mt-2 group-extra-fields">
                                                                    <div class="col-md-12">
                                                                        <div class="form-check">
                                                                            <input type="checkbox" class="form-check-input" 
                                                                                   name="groups[<?= $index ?>][with_rollup]" value="1"
                                                                                   <?= $group['with_rollup'] ? 'checked' : '' ?>>
                                                                            <label class="form-check-label">Include ROLLUP totals</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-center mt-3">
                                                <button type="button" class="btn btn-outline-success btn-sm me-2" onclick="addRollupGroup()">
                                                    <i class="fas fa-chart-line"></i> Add ROLLUP
                                                </button>
                                                <button type="button" class="btn btn-outline-success btn-sm" onclick="addExpressionGroup()">
                                                    <i class="fas fa-code"></i> Add Expression
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ORDER BY Section (NEW) -->
                    <div class="card section-card complex-section">
                        <div class="card-header bg-purple text-white d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="section-icon bg-white text-purple">
                                    <i class="fas fa-sort-amount-down"></i>
                                </div>
                                <h5 class="mb-0">ORDER BY</h5>
                                <span class="badge bg-light text-dark ms-2" id="orderCount">0</span>
                            </div>
                            <div>
                                <button type="button" class="btn btn-light btn-sm me-2" onclick="addSimpleOrder()">
                                    <i class="fas fa-plus"></i> Simple
                                </button>
                                <button type="button" class="btn btn-light btn-sm" onclick="addComplexOrder()">
                                    <i class="fas fa-cogs"></i> Advanced
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="orderByAccordion">
                                <!-- Simple ORDER BY -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#simpleOrderBy">
                                            <i class="fas fa-list me-2"></i> Simple Sorting
                                        </button>
                                    </h2>
                                    <div id="simpleOrderBy" class="accordion-collapse collapse" data-bs-parent="#orderByAccordion">
                                        <div class="accordion-body">
                                            <div id="simpleOrdersContainer" class="sortable-container">
                                                <?php if (!empty($report['orders'])): ?>
                                                    <?php foreach ($report['orders'] as $index => $order): ?>
                                                        <?php if (($order['order_type'] ?? 'COLUMN') == 'COLUMN'): ?>
                                                            <div class="draggable-item simple-order-item mb-2" data-index="<?= $index ?>">
                                                                <div class="row g-2">
                                                                    <div class="col-md-1 text-center pt-2">
                                                                        <i class="fas fa-grip-vertical text-muted"></i>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <input type="text" class="form-control form-control-sm" 
                                                                               name="orders[<?= $index ?>][order_expression]"
                                                                               value="<?= htmlspecialchars($order['order_expression']) ?>"
                                                                               placeholder="Column to sort by">
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <select class="form-select form-select-sm" 
                                                                                name="orders[<?= $index ?>][direction]">
                                                                            <option value="ASC" <?= ($order['direction'] ?? 'ASC') == 'ASC' ? 'selected' : '' ?>>ASC (A-Z)</option>
                                                                            <option value="DESC" <?= ($order['direction'] ?? 'ASC') == 'DESC' ? 'selected' : '' ?>>DESC (Z-A)</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <button type="button" class="btn btn-danger btn-sm w-100 remove-btn" onclick="removeSimpleOrder(this)">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                <input type="hidden" name="orders[<?= $index ?>][order_type]" value="COLUMN">
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-center mt-3">
                                                <button type="button" class="btn btn-outline-purple btn-sm" onclick="addSimpleOrder()">
                                                    <i class="fas fa-plus-circle"></i> Add Sort Column
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Advanced ORDER BY -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#advancedOrderBy">
                                            <i class="fas fa-cogs me-2"></i> Advanced Sorting
                                        </button>
                                    </h2>
                                    <div id="advancedOrderBy" class="accordion-collapse collapse" data-bs-parent="#orderByAccordion">
                                        <div class="accordion-body">
                                            <div id="complexOrdersContainer">
                                                <?php if (!empty($report['orders'])): ?>
                                                    <?php foreach ($report['orders'] as $index => $order): ?>
                                                        <?php if (($order['order_type'] ?? 'COLUMN') != 'COLUMN'): ?>
                                                            <div class="complex-order-item mb-3 p-3 border rounded">
                                                                <div class="row">
                                                                    <div class="col-md-3">
                                                                        <label class="form-label">Order Type</label>
                                                                        <select class="form-control form-control-sm order-type-select" 
                                                                                name="orders[<?= $index ?>][order_type]">
                                                                            <option value="EXPRESSION" <?= $order['order_type'] == 'EXPRESSION' ? 'selected' : '' ?>>Expression</option>
                                                                            <option value="CASE" <?= $order['order_type'] == 'CASE' ? 'selected' : '' ?>>CASE WHEN</option>
                                                                            <option value="FUNCTION" <?= $order['order_type'] == 'FUNCTION' ? 'selected' : '' ?>>Function</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Expression</label>
                                                                        <textarea class="form-control form-control-sm order-expression" 
                                                                                  name="orders[<?= $index ?>][order_expression]"
                                                                                  rows="2"><?= htmlspecialchars($order['order_expression']) ?></textarea>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <label class="form-label">Settings</label>
                                                                        <div class="input-group input-group-sm">
                                                                            <select class="form-select" name="orders[<?= $index ?>][direction]">
                                                                                <option value="ASC" <?= ($order['direction'] ?? 'ASC') == 'ASC' ? 'selected' : '' ?>>ASC</option>
                                                                                <option value="DESC" <?= ($order['direction'] ?? 'ASC') == 'DESC' ? 'selected' : '' ?>>DESC</option>
                                                                            </select>
                                                                            <select class="form-select" name="orders[<?= $index ?>][nulls_order]">
                                                                                <option value="">NULL Default</option>
                                                                                <option value="NULLS FIRST" <?= ($order['nulls_order'] ?? '') == 'NULLS FIRST' ? 'selected' : '' ?>>NULLS FIRST</option>
                                                                                <option value="NULLS LAST" <?= ($order['nulls_order'] ?? '') == 'NULLS LAST' ? 'selected' : '' ?>>NULLS LAST</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row mt-2">
                                                                    <div class="col-md-12">
                                                                        <button type="button" class="btn btn-danger btn-sm remove-btn" onclick="removeComplexOrder(this)">
                                                                            <i class="fas fa-trash"></i> Remove
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-center mt-3">
                                                <button type="button" class="btn btn-outline-purple btn-sm me-2" onclick="addCaseOrder()">
                                                    <i class="fas fa-random"></i> Add CASE WHEN
                                                </button>
                                                <button type="button" class="btn btn-outline-purple btn-sm" onclick="addFunctionOrder()">
                                                    <i class="fas fa-function"></i> Add Function
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Panel: Tools and Preview -->
                <div class="col-md-4">
                    <!-- Quick Actions -->
                    <div class="card mb-4">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-primary" onclick="previewSql()">
                                    <i class="fas fa-eye"></i> Preview SQL
                                </button>
                                <button type="button" class="btn btn-success" onclick="validateReport()">
                                    <i class="fas fa-check-circle"></i> Validate Report
                                </button>
                                <button type="button" class="btn btn-info" onclick="saveReport()">
                                    <i class="fas fa-save"></i> Save Report
                                </button>
                                <button type="button" class="btn btn-warning" onclick="saveAndExecute()">
                                    <i class="fas fa-play"></i> Save & Run
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- SQL Preview -->
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0"><i class="fas fa-code"></i> SQL Preview</h6>
                        </div>
                        <div class="card-body">
                            <div id="sqlPreview" class="sql-preview p-3 mb-3">
                                <em class="text-muted">SQL will appear here...</em>
                            </div>
                            <div class="d-grid">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="copySql()">
                                    <i class="fas fa-copy"></i> Copy SQL
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Database Explorer -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-database"></i> Database Explorer</h6>
                        </div>
                        <div class="card-body table-list">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control form-control-sm" 
                                       id="tableSearch" placeholder="Search tables...">
                                <button class="btn btn-outline-secondary btn-sm" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div id="tableList">
                                <?php foreach ($tables as $table): ?>
                                    <div class="table-item mb-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="table-name"><?= $table ?></span>
                                            <div>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="showTableColumns('<?= $table ?>')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-success" 
                                                        onclick="insertTableName('<?= $table ?>')">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Report Stats -->
                    <div class="card mt-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Report Stats</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="stat-number" id="statColumns">0</div>
                                    <small class="text-muted">Columns</small>
                                </div>
                                <div class="col-6">
                                    <div class="stat-number" id="statJoins">0</div>
                                    <small class="text-muted">JOINs</small>
                                </div>
                                <div class="col-6 mt-3">
                                    <div class="stat-number" id="statConditions">0</div>
                                    <small class="text-muted">Conditions</small>
                                </div>
                                <div class="col-6 mt-3">
                                    <div class="stat-number" id="statGroups">0</div>
                                    <small class="text-muted">GROUP BY</small>
                                </div>
                                <div class="col-6 mt-3">
                                    <div class="stat-number" id="statOrders">0</div>
                                    <small class="text-muted">ORDER BY</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Modals -->
    <!-- Table Columns Modal -->
    <div class="modal fade" id="columnsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Table Columns: <span id="modalTableName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="columnSearch" placeholder="Search columns...">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="columnsTableBody">
                                <!-- Columns loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Condition Builder Modal -->
    <div class="modal fade" id="conditionBuilderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Condition Builder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Field</label>
                                <input type="text" class="form-control" id="conditionField" placeholder="Column name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Operator</label>
                                <select class="form-select" id="conditionOperator">
                                    <option value="=">=</option>
                                    <option value="!=">!=</option>
                                    <option value=">">></option>
                                    <option value="<"><</option>
                                    <option value=">=">>=</option>
                                    <option value="<="><=</option>
                                    <option value="LIKE">LIKE</option>
                                    <option value="NOT LIKE">NOT LIKE</option>
                                    <option value="IN">IN</option>
                                    <option value="NOT IN">NOT IN</option>
                                    <option value="BETWEEN">BETWEEN</option>
                                    <option value="IS NULL">IS NULL</option>
                                    <option value="IS NOT NULL">IS NOT NULL</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Value</label>
                                <input type="text" class="form-control" id="conditionValue" placeholder="Value or expression">
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <small><i class="fas fa-info-circle"></i> Use :param_name for parameters. For IN clauses, use comma-separated values.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="applyCondition()">Apply Condition</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Expression Builder Modal -->
    <div class="modal fade" id="expressionBuilderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Expression Builder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="list-group">
                                <button class="list-group-item list-group-item-action" onclick="addToExpression('CONCAT(')">
                                    <i class="fas fa-link"></i> CONCAT()
                                </button>
                                <button class="list-group-item list-group-item-action" onclick="addToExpression('CASE WHEN THEN END')">
                                    <i class="fas fa-random"></i> CASE WHEN
                                </button>
                                <button class="list-group-item list-group-item-action" onclick="addToExpression('COALESCE(')">
                                    <i class="fas fa-exchange-alt"></i> COALESCE()
                                </button>
                                <button class="list-group-item list-group-item-action" onclick="addToExpression('DATE_FORMAT(')">
                                    <i class="fas fa-calendar"></i> DATE_FORMAT()
                                </button>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Expression</label>
                                <textarea class="form-control" id="expressionBuilder" rows="6" placeholder="Build your expression here..."></textarea>
                            </div>
                            <div class="alert alert-info">
                                <small><i class="fas fa-lightbulb"></i> Tip: Click table columns on the right to insert them</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="applyExpression()">Apply Expression</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/sql/sql.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
    
    <script>
        // Global counters
        let columnIndex = <?= count($report['columns'] ?? []) ?>;
        let joinIndex = <?= count($report['joins'] ?? []) ?>;
        let conditionIndex = <?= count($report['conditions'] ?? []) ?>;
        let simpleGroupIndex = <?= count(array_filter($report['groups'] ?? [], function($g) { return ($g['group_type'] ?? 'COLUMN') == 'COLUMN'; })) ?>;
        let complexGroupIndex = <?= count(array_filter($report['groups'] ?? [], function($g) { return ($g['group_type'] ?? 'COLUMN') != 'COLUMN'; })) ?>;
        let simpleOrderIndex = <?= count(array_filter($report['orders'] ?? [], function($o) { return ($o['order_type'] ?? 'COLUMN') == 'COLUMN'; })) ?>;
        let complexOrderIndex = <?= count(array_filter($report['orders'] ?? [], function($o) { return ($o['order_type'] ?? 'COLUMN') != 'COLUMN'; })) ?>;
        
        // Target elements for modals
        let currentConditionTarget = null;
        let currentExpressionTarget = null;
        
        $(document).ready(function() {
            // Initialize Select2
            $('.select2-table').select2({
                placeholder: "Select a table",
                allowClear: true
            });
            
            // Initialize sortable containers
            initializeSortables();
            
            // Update stats
            updateStats();
            
            // Search tables
            $('#tableSearch').on('keyup', function() {
                const search = $(this).val().toLowerCase();
                $('.table-item').each(function() {
                    const tableName = $(this).find('.table-name').text().toLowerCase();
                    $(this).toggle(tableName.includes(search));
                });
            });
            
            // Search columns in modal
            $('#columnSearch').on('keyup', function() {
                const search = $(this).val().toLowerCase();
                $('#columnsTableBody tr').each(function() {
                    const colName = $(this).find('td:first').text().toLowerCase();
                    $(this).toggle(colName.includes(search));
                });
            });
        });
        
        function initializeSortables() {
            // Make columns sortable
            new Sortable(document.getElementById('columnsContainer'), {
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function() {
                    reindexColumns();
                }
            });
            
            // Make joins sortable
            new Sortable(document.getElementById('joinsContainer'), {
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function() {
                    reindexJoins();
                }
            });
            
            // Make simple groups sortable
            new Sortable(document.getElementById('simpleGroupsContainer'), {
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function() {
                    reindexSimpleGroups();
                }
            });
            
            // Make simple orders sortable
            new Sortable(document.getElementById('simpleOrdersContainer'), {
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function() {
                    reindexSimpleOrders();
                }
            });
        }
        
        function updateStats() {
            $('#columnCount').text($('.column-item').length);
            $('#joinCount').text($('.join-item').length);
            $('#conditionCount').text($('.condition-item').length);
            $('#groupCount').text($('.simple-group-item').length + $('.complex-group-item').length);
            $('#orderCount').text($('.simple-order-item').length + $('.complex-order-item').length);
            
            $('#statColumns').text($('.column-item').length);
            $('#statJoins').text($('.join-item').length);
            $('#statConditions').text($('.condition-item').length);
            $('#statGroups').text($('.simple-group-item').length + $('.complex-group-item').length);
            $('#statOrders').text($('.simple-order-item').length + $('.complex-order-item').length);
        }
        
        // ===== COLUMN FUNCTIONS =====
        function addColumn() {
            const html = `
                <div class="draggable-item column-item" data-index="${columnIndex}">
                    <div class="row g-2">
                        <div class="col-md-1 text-center pt-2">
                            <i class="fas fa-grip-vertical text-muted"></i>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control form-control-sm" 
                                   name="columns[${columnIndex}][column_expression]"
                                   placeholder="Expression (e.g., table.column, CONCAT(...))">
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control form-control-sm" 
                                   name="columns[${columnIndex}][alias]"
                                   placeholder="Alias">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select form-select-sm" 
                                    name="columns[${columnIndex}][aggregate_function]">
                                <option value="">No Aggregate</option>
                                <option value="SUM">SUM</option>
                                <option value="COUNT">COUNT</option>
                                <option value="AVG">AVG</option>
                                <option value="MIN">MIN</option>
                                <option value="MAX">MAX</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select form-select-sm" 
                                    name="columns[${columnIndex}][data_type]">
                                <option value="string">String</option>
                                <option value="number">Number</option>
                                <option value="date">Date</option>
                                <option value="datetime">DateTime</option>
                                <option value="boolean">Boolean</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger btn-sm w-100 remove-btn" onclick="removeColumn(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>`;
            $('#columnsContainer').append(html);
            columnIndex++;
            updateStats();
        }
        
        function removeColumn(button) {
            $(button).closest('.column-item').remove();
            reindexColumns();
            updateStats();
        }
        
        function reindexColumns() {
            $('.column-item').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('[name*="column_expression"]').attr('name', `columns[${index}][column_expression]`);
                $(this).find('[name*="alias"]').attr('name', `columns[${index}][alias]`);
                $(this).find('[name*="aggregate_function"]').attr('name', `columns[${index}][aggregate_function]`);
                $(this).find('[name*="data_type"]').attr('name', `columns[${index}][data_type]`);
            });
            columnIndex = $('.column-item').length;
        }
        
        // ===== JOIN FUNCTIONS =====
        function addJoin() {
            const html = `
                <div class="draggable-item join-item" data-index="${joinIndex}">
                    <div class="row g-2">
                        <div class="col-md-1 text-center pt-2">
                            <i class="fas fa-grip-vertical text-muted"></i>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select form-select-sm" 
                                    name="joins[${joinIndex}][join_type]">
                                <option value="INNER">INNER</option>
                                <option value="LEFT">LEFT</option>
                                <option value="RIGHT">RIGHT</option>
                                <option value="FULL OUTER">FULL OUTER</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm join-table" 
                                    name="joins[${joinIndex}][table_name]">
                                <option value="">Select table</option>
                                <?php foreach ($tables as $table): ?>
                                    <option value="<?= $table ?>"><?= $table ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control form-control-sm" 
                                   name="joins[${joinIndex}][alias]"
                                   placeholder="Alias">
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control form-control-sm" 
                                   name="joins[${joinIndex}][join_condition]"
                                   placeholder="Condition (e.g., t1.id = t2.foreign_id)">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger btn-sm w-100 remove-btn" onclick="removeJoin(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>`;
            $('#joinsContainer').append(html);
            joinIndex++;
            updateStats();
        }
        
        function removeJoin(button) {
            $(button).closest('.join-item').remove();
            reindexJoins();
            updateStats();
        }
        
        function reindexJoins() {
            $('.join-item').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('[name*="join_type"]').attr('name', `joins[${index}][join_type]`);
                $(this).find('[name*="table_name"]').attr('name', `joins[${index}][table_name]`);
                $(this).find('[name*="alias"]').attr('name', `joins[${index}][alias]`);
                $(this).find('[name*="join_condition"]').attr('name', `joins[${index}][join_condition]`);
            });
            joinIndex = $('.join-item').length;
        }
        
        // ===== CONDITION FUNCTIONS =====
        function addCondition() {
            const html = `
                <div class="draggable-item condition-item mb-2" data-index="${conditionIndex}">
                    <div class="row g-2">
                        <div class="col-md-1 text-center pt-2">
                            <i class="fas fa-grip-vertical text-muted"></i>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" 
                                    name="conditions[${conditionIndex}][condition_type]">
                                <option value="WHERE">WHERE</option>
                                <option value="HAVING">HAVING</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" 
                                       name="conditions[${conditionIndex}][condition_expression]"
                                       placeholder="Condition (e.g., column = value)">
                                <button type="button" class="btn btn-outline-secondary" 
                                        onclick="showConditionBuilder(this)">
                                    <i class="fas fa-cogs"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <select class="form-select form-select-sm" 
                                    name="conditions[${conditionIndex}][operator]">
                                <option value="AND">AND</option>
                                <option value="OR">OR</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger btn-sm w-100 remove-btn" onclick="removeCondition(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" 
                                       name="conditions[${conditionIndex}][is_parameter]" value="1"
                                       onchange="toggleParameterFields(this, ${conditionIndex})">
                                <label class="form-check-label">Use Parameter</label>
                            </div>
                        </div>
                        <div class="col-md-6 parameter-fields-${conditionIndex}" style="display: none;">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" 
                                       name="conditions[${conditionIndex}][parameter_name]"
                                       placeholder="Parameter name">
                                <input type="text" class="form-control" 
                                       name="conditions[${conditionIndex}][parameter_default]"
                                       placeholder="Default value">
                            </div>
                        </div>
                    </div>
                </div>`;
            $('#conditionsContainer').append(html);
            conditionIndex++;
            updateStats();
        }
        
        function removeCondition(button) {
            $(button).closest('.condition-item').remove();
            updateStats();
        }
        
        function toggleParameterFields(checkbox, index) {
            const $fields = $(`.parameter-fields-${index}`);
            if (checkbox.checked) {
                $fields.show();
            } else {
                $fields.hide();
            }
        }
        
        function showConditionBuilder(button) {
            currentConditionTarget = $(button).closest('.input-group').find('input');
            $('#conditionBuilderModal').modal('show');
        }
        
        function applyCondition() {
            if (currentConditionTarget) {
                const field = $('#conditionField').val();
                const operator = $('#conditionOperator').val();
                const value = $('#conditionValue').val();
                
                let condition = '';
                if (operator === 'IS NULL' || operator === 'IS NOT NULL') {
                    condition = `${field} ${operator}`;
                } else if (operator === 'IN' || operator === 'NOT IN') {
                    condition = `${field} ${operator} (${value})`;
                } else if (operator === 'BETWEEN') {
                    const values = value.split(',');
                    condition = `${field} ${operator} ${values[0] || ''} AND ${values[1] || ''}`;
                } else {
                    condition = `${field} ${operator} ${value}`;
                }
                
                currentConditionTarget.val(condition);
                $('#conditionBuilderModal').modal('hide');
                
                // Reset form
                $('#conditionField').val('');
                $('#conditionValue').val('');
            }
        }
        
        // ===== GROUP BY FUNCTIONS =====
        function addSimpleGroup() {

            // new 06-02-2026
            $('#simpleGroupBy').collapse('show');
            // end
            const html = `
                <div class="draggable-item simple-group-item mb-2" data-index="${simpleGroupIndex}">
                    <div class="row g-2">
                        <div class="col-md-1 text-center pt-2">
                            <i class="fas fa-grip-vertical text-muted"></i>
                        </div>
                        <div class="col-md-9">
                            <input type="text" class="form-control form-control-sm" 
                                   name="groups[${simpleGroupIndex}][group_expression]"
                                   placeholder="Column to group by (e.g., department_id, YEAR(created_at))">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger btn-sm w-100 remove-btn" onclick="removeSimpleGroup(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="groups[${simpleGroupIndex}][group_type]" value="COLUMN">
                </div>`;
            $('#simpleGroupsContainer').append(html);
            simpleGroupIndex++;
            updateStats();
        }
        
        function addComplexGroup() {
            $('#advancedGroupBy').collapse('show');
        }
        
        function addRollupGroup() {
            const html = `
                <div class="complex-group-item mb-3 p-3 border rounded">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Group Type</label>
                            <select class="form-control form-control-sm group-type-select" 
                                    name="groups[${complexGroupIndex}][group_type]">
                                <option value="ROLLUP">WITH ROLLUP</option>
                                <option value="EXPRESSION">Expression</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Expression</label>
                            <textarea class="form-control form-control-sm group-expression" 
                                      name="groups[${complexGroupIndex}][group_expression]"
                                      rows="2" placeholder="Enter columns for ROLLUP, e.g., department_id, category_id"></textarea>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Actions</label>
                            <button type="button" class="btn btn-danger btn-sm w-100 remove-btn" onclick="removeComplexGroup(this)">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                    <div class="row mt-2 group-extra-fields">
                        <div class="col-md-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" 
                                       name="groups[${complexGroupIndex}][with_rollup]" value="1" checked>
                                <label class="form-check-label">Include ROLLUP totals</label>
                            </div>
                        </div>
                    </div>
                </div>`;
            $('#complexGroupsContainer').append(html);
            complexGroupIndex++;
            updateStats();
        }
        
        function addExpressionGroup() {
            const html = `
                <div class="complex-group-item mb-3 p-3 border rounded">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Group Type</label>
                            <select class="form-control form-control-sm group-type-select" 
                                    name="groups[${complexGroupIndex}][group_type]">
                                <option value="EXPRESSION">Expression</option>
                                <option value="ROLLUP">WITH ROLLUP</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Expression</label>
                            <textarea class="form-control form-control-sm group-expression" 
                                      name="groups[${complexGroupIndex}][group_expression]"
                                      rows="2" placeholder="e.g., YEAR(created_date), MONTH(created_date)"></textarea>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Actions</label>
                            <button type="button" class="btn btn-danger btn-sm w-100 remove-btn" onclick="removeComplexGroup(this)">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                    <div class="row mt-2 group-extra-fields">
                        <div class="col-md-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" 
                                       name="groups[${complexGroupIndex}][with_rollup]" value="1">
                                <label class="form-check-label">Include ROLLUP totals</label>
                            </div>
                        </div>
                    </div>
                </div>`;
            $('#complexGroupsContainer').append(html);
            complexGroupIndex++;
            updateStats();
        }
        
        function removeSimpleGroup(button) {
            $(button).closest('.simple-group-item').remove();
            reindexSimpleGroups();
            updateStats();
        }
        
        function removeComplexGroup(button) {
            $(button).closest('.complex-group-item').remove();
            updateStats();
        }
        
        function reindexSimpleGroups() {
            $('.simple-group-item').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('[name*="group_expression"]').attr('name', `groups[${index}][group_expression]`);
                $(this).find('[name*="group_type"]').attr('name', `groups[${index}][group_type]`);
            });
            simpleGroupIndex = $('.simple-group-item').length;
        }
        
        // ===== ORDER BY FUNCTIONS =====
        function addSimpleOrder() {

            // new 06-02-2026
            $('#simpleOrderBy').collapse('show');
            // end
            const html = `
                <div class="draggable-item simple-order-item mb-2" data-index="${simpleOrderIndex}">
                    <div class="row g-2">
                        <div class="col-md-1 text-center pt-2">
                            <i class="fas fa-grip-vertical text-muted"></i>
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control form-control-sm" 
                                   name="orders[${simpleOrderIndex}][order_expression]"
                                   placeholder="Column to sort by">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" 
                                    name="orders[${simpleOrderIndex}][direction]">
                                <option value="ASC">ASC (A-Z)</option>
                                <option value="DESC">DESC (Z-A)</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger btn-sm w-100 remove-btn" onclick="removeSimpleOrder(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="orders[${simpleOrderIndex}][order_type]" value="COLUMN">
                </div>`;
            $('#simpleOrdersContainer').append(html);
            simpleOrderIndex++;
            updateStats();
        }
        
        function addComplexOrder() {
            $('#advancedOrderBy').collapse('show');
        }
        
        function addCaseOrder() {
            const html = `
                <div class="complex-order-item mb-3 p-3 border rounded">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Order Type</label>
                            <select class="form-control form-control-sm order-type-select" 
                                    name="orders[${complexOrderIndex}][order_type]">
                                <option value="CASE">CASE WHEN</option>
                                <option value="EXPRESSION">Expression</option>
                                <option value="FUNCTION">Function</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expression</label>
                            <textarea class="form-control form-control-sm order-expression" 
                                      name="orders[${complexOrderIndex}][order_expression]"
                                      rows="2" placeholder="CASE WHEN status = 'Active' THEN 1 WHEN status = 'Pending' THEN 2 ELSE 3 END"></textarea>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Settings</label>
                            <div class="input-group input-group-sm">
                                <select class="form-select" name="orders[${complexOrderIndex}][direction]">
                                    <option value="ASC">ASC</option>
                                    <option value="DESC">DESC</option>
                                </select>
                                <select class="form-select" name="orders[${complexOrderIndex}][nulls_order]">
                                    <option value="">NULL Default</option>
                                    <option value="NULLS FIRST">NULLS FIRST</option>
                                    <option value="NULLS LAST">NULLS LAST</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-danger btn-sm remove-btn" onclick="removeComplexOrder(this)">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>`;
            $('#complexOrdersContainer').append(html);
            complexOrderIndex++;
            updateStats();
        }
        
        function addFunctionOrder() {
            const html = `
                <div class="complex-order-item mb-3 p-3 border rounded">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Order Type</label>
                            <select class="form-control form-control-sm order-type-select" 
                                    name="orders[${complexOrderIndex}][order_type]">
                                <option value="FUNCTION">Function</option>
                                <option value="EXPRESSION">Expression</option>
                                <option value="CASE">CASE WHEN</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expression</label>
                            <textarea class="form-control form-control-sm order-expression" 
                                      name="orders[${complexOrderIndex}][order_expression]"
                                      rows="2" placeholder="e.g., LENGTH(username), UPPER(last_name)"></textarea>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Settings</label>
                            <div class="input-group input-group-sm">
                                <select class="form-select" name="orders[${complexOrderIndex}][direction]">
                                    <option value="ASC">ASC</option>
                                    <option value="DESC">DESC</option>
                                </select>
                                <select class="form-select" name="orders[${complexOrderIndex}][nulls_order]">
                                    <option value="">NULL Default</option>
                                    <option value="NULLS FIRST">NULLS FIRST</option>
                                    <option value="NULLS LAST">NULLS LAST</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-danger btn-sm remove-btn" onclick="removeComplexOrder(this)">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>`;
            $('#complexOrdersContainer').append(html);
            complexOrderIndex++;
            updateStats();
        }
        
        function removeSimpleOrder(button) {
            $(button).closest('.simple-order-item').remove();
            reindexSimpleOrders();
            updateStats();
        }
        
        function removeComplexOrder(button) {
            $(button).closest('.complex-order-item').remove();
            updateStats();
        }
        
        function reindexSimpleOrders() {
            $('.simple-order-item').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('[name*="order_expression"]').attr('name', `orders[${index}][order_expression]`);
                $(this).find('[name*="direction"]').attr('name', `orders[${index}][direction]`);
                $(this).find('[name*="order_type"]').attr('name', `orders[${index}][order_type]`);
            });
            simpleOrderIndex = $('.simple-order-item').length;
        }
        
        // ===== DATABASE FUNCTIONS =====
        function loadTableColumns() {
            const table = $('#baseTable').val();
            if (!table) {
                alert('Please select a base table first');
                return;
            }
            showTableColumns(table);
        }
        
        function showTableColumns(tableName) {
            $('#modalTableName').text(tableName);
            $('#columnsTableBody').html('<tr><td colspan="3" class="text-center">Loading columns...</td></tr>');
            
            $.ajax({
                url: '<?= base_url("reports/get-table-columns") ?>',
                type: 'GET',
                data: { table: tableName },
                success: function(response) {
                    if (response.status === 'success') {
                        let html = '';
                        if (response.columns.length === 0) {
                            html = '<tr><td colspan="3" class="text-center">No columns found</td></tr>';
                        } else {
                            response.columns.forEach(function(column) {
                                html += `
                                    <tr>
                                        <td>${column.name}</td>
                                        <td><span class="badge bg-secondary">${column.type}</span></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                                    onclick="insertColumn('${tableName}.${column.name}')">
                                                <i class="fas fa-plus"></i> Select
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                    onclick="insertColumn('${column.name}')">
                                                <i class="fas fa-code"></i> Name Only
                                            </button>
                                        </td>
                                    </tr>`;
                            });
                        }
                        $('#columnsTableBody').html(html);
                    } else {
                        $('#columnsTableBody').html('<tr><td colspan="3" class="text-center text-danger">Error loading columns</td></tr>');
                    }
                },
                error: function() {
                    $('#columnsTableBody').html('<tr><td colspan="3" class="text-center text-danger">Error loading columns</td></tr>');
                }
            });
            
            $('#columnsModal').modal('show');
        }
        
        function insertColumn(columnName) {
            // Insert into focused input
            const focused = document.activeElement;
            if (focused && (focused.tagName === 'INPUT' || focused.tagName === 'TEXTAREA' || focused.tagName === 'BUTTON')) {
                const start = focused.selectionStart;
                const end = focused.selectionEnd;
                const value = focused.value;
                focused.value = value.substring(0, start) + columnName + value.substring(end);
                focused.selectionStart = focused.selectionEnd = start + columnName.length;
                focused.focus();
            }
        }
        
        function insertTableName(tableName) {
            // Find first empty column expression or condition
            const $emptyColumn = $('.column-item [name*="column_expression"]').filter(function() {
                return !$(this).val();
            }).first();
            
            if ($emptyColumn.length) {
                $emptyColumn.val(tableName + '.');
                $emptyColumn.focus();
            } else {
                // Add new column with table name
                addColumn();
                setTimeout(() => {
                    $('.column-item:last [name*="column_expression"]').val(tableName + '.');
                }, 100);
            }
        }
        
        // ===== EXPRESSION BUILDER =====
        function showExpressionBuilder(button) {
            currentExpressionTarget = $(button).closest('.input-group').find('input, textarea');
            $('#expressionBuilderModal').modal('show');
        }
        
        function addToExpression(text) {
            const $textarea = $('#expressionBuilder');
            const current = $textarea.val();
            const cursorPos = $textarea.prop('selectionStart');
            
            $textarea.val(current.substring(0, cursorPos) + text + current.substring(cursorPos));
            $textarea.focus();
            $textarea.prop('selectionStart', cursorPos + text.length);
            $textarea.prop('selectionEnd', cursorPos + text.length);
        }
        
        function applyExpression() {
            if (currentExpressionTarget) {
                const expression = $('#expressionBuilder').val();
                if (expression) {
                    currentExpressionTarget.val(expression);
                }
                $('#expressionBuilderModal').modal('hide');
                $('#expressionBuilder').val('');
            }
        }
        
        // ===== REPORT FUNCTIONS =====
        // function previewSql() {
        //     const formData = collectFormData();
            
        //     $.ajax({
        //         url: '<?= base_url("reports/preview-sql") ?>',
        //         type: 'POST',
        //         data: JSON.stringify(formData),
        //         contentType: 'application/json',
        //         success: function(response) {
        //             if (response.status === 'success') {
        //                 $('#sqlPreview').html(`<pre class="p-3 bg-dark text-light">${response.sql}</pre>`);
        //             } else {
        //                 $('#sqlPreview').html(`<div class="alert alert-danger">${response.message || 'Error generating SQL'}</div>`);
        //             }
        //         },
        //         error: function(xhr) {
        //             $('#sqlPreview').html(`<div class="alert alert-danger">Error: ${xhr.responseText || 'Unknown error'}</div>`);
        //         }
        //     });
        // }

        function previewSql() {
    const formData = collectFormData();
    
    // Validate before sending
    if (!formData.base_table) {
        $('#sqlPreview').html(`<div class="alert alert-warning">Please select a base table first</div>`);
        return;
    }
    
    if (formData.columns.length === 0) {
        $('#sqlPreview').html(`<div class="alert alert-warning">Please add at least one column</div>`);
        return;
    }
    
    // Show loading indicator
    $('#sqlPreview').html(`<div class="text-center py-3">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Generating SQL...</p>
    </div>`);
    
    $.ajax({
        url: '<?= base_url("reports/preview-sql") ?>',
        type: 'POST',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                // Format SQL with syntax highlighting
                const formattedSql = formatSql(response.sql);
                $('#sqlPreview').html(`<div class="sql-output"><pre class="p-3 bg-dark text-light">${formattedSql}</pre></div>`);
                
                // Add copy button
                $('#sqlPreview').append(`
                    <div class="mt-2 text-end">
                        <button type="button" class="btn btn-sm btn-outline-light" onclick="copySqlToClipboard()">
                            <i class="fas fa-copy"></i> Copy SQL
                        </button>
                    </div>
                `);
            } else {
                $('#sqlPreview').html(`<div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> ${response.message || 'Error generating SQL'}
                </div>`);
            }
        },
        error: function(xhr, status, error) {
            let errorMessage = 'Unknown error occurred';
            if (xhr.responseJSON && xhr.responseJSON.messages) {
                errorMessage = xhr.responseJSON.messages.error || errorMessage;
            } else if (xhr.responseText) {
                try {
                    const errorObj = JSON.parse(xhr.responseText);
                    errorMessage = errorObj.message || errorObj.error || errorMessage;
                } catch (e) {
                    errorMessage = xhr.responseText.substring(0, 200);
                }
            }
            
            $('#sqlPreview').html(`<div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> Error: ${errorMessage}
            </div>`);
        }
    });
}

function formatSql(sql) {
    // Basic SQL formatting
    const keywords = [
        'SELECT', 'FROM', 'WHERE', 'JOIN', 'LEFT JOIN', 'RIGHT JOIN', 'INNER JOIN', 
        'GROUP BY', 'ORDER BY', 'HAVING', 'AND', 'OR', 'AS', 'ON', 'IN', 'LIKE',
        'BETWEEN', 'IS NULL', 'IS NOT NULL', 'CASE', 'WHEN', 'THEN', 'ELSE', 'END',
        'SUM', 'COUNT', 'AVG', 'MIN', 'MAX', 'DISTINCT', 'ASC', 'DESC'
    ];
    
    let formatted = sql;
    
    // Add line breaks after major clauses
    formatted = formatted.replace(/SELECT /gi, 'SELECT\n  ');
    formatted = formatted.replace(/FROM /gi, '\nFROM\n  ');
    formatted = formatted.replace(/WHERE /gi, '\nWHERE\n  ');
    formatted = formatted.replace(/(LEFT|RIGHT|INNER|FULL OUTER)? JOIN /gi, '\n$&');
    formatted = formatted.replace(/GROUP BY /gi, '\nGROUP BY\n  ');
    formatted = formatted.replace(/ORDER BY /gi, '\nORDER BY\n  ');
    formatted = formatted.replace(/HAVING /gi, '\nHAVING\n  ');
    
    // Indent conditions
    formatted = formatted.replace(/AND /gi, '  AND ');
    formatted = formatted.replace(/OR /gi, '  OR ');
    
    return formatted;
}

function copySqlToClipboard() {
    const sqlText = $('#sqlPreview .sql-output pre').text();
    if (sqlText) {
        navigator.clipboard.writeText(sqlText).then(() => {
            // Show success feedback
            const $btn = $('#sqlPreview button');
            const originalHtml = $btn.html();
            $btn.html('<i class="fas fa-check"></i> Copied!');
            setTimeout(() => {
                $btn.html(originalHtml);
            }, 2000);
        }).catch(err => {
            alert('Failed to copy: ' + err);
        });
    }
}
        
        function copySql() {
            const sqlText = $('#sqlPreview pre').text();
            if (sqlText) {
                navigator.clipboard.writeText(sqlText).then(() => {
                    alert('SQL copied to clipboard!');
                });
            }
        }
        
        function validateReport() {
            const formData = collectFormData();
            
            // Basic validation
            if (!formData.report_name) {
                alert('Please enter a report name');
                return false;
            }
            
            if (!formData.base_table) {
                alert('Please select a base table');
                return false;
            }
            
            if (formData.columns.length === 0) {
                alert('Please add at least one column');
                return false;
            }
            
            // Check for duplicate aliases
            const aliases = formData.columns.map(col => col.alias).filter(alias => alias);
            const duplicateAliases = aliases.filter((alias, index) => aliases.indexOf(alias) !== index);
            
            if (duplicateAliases.length > 0) {
                alert(`Duplicate column aliases found: ${duplicateAliases.join(', ')}`);
                return false;
            }
            
            alert('Report validation passed!');
            return true;
        }
        
        function saveReport() {
            if (!validateReport()) {
                return;
            }
            
            const formData = new FormData(document.getElementById('reportForm'));
            
            $.ajax({
                url: '<?= base_url("reports/save") ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        alert('Report saved successfully!');
                        // Update form with new ID
                        $('input[name="id"]').val(response.report_id);
                        $('#reportStatus').text('Editing Report').removeClass('bg-info').addClass('bg-success');
                    } else {
                        alert('Error: ' + (response.message || 'Failed to save report'));
                    }
                },
                error: function(xhr) {
                    alert('Error: ' + xhr.responseText);
                }
            });
        }
        
        function saveAndExecute() {
            if (!validateReport()) {
                return;
            }
            
            const formData = new FormData(document.getElementById('reportForm'));
            
            $.ajax({
                url: '<?= base_url("reports/save") ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        window.location.href = '<?= base_url("reports/execute") ?>/' + response.report_id;
                    } else {
                        alert('Error: ' + (response.message || 'Failed to save report'));
                    }
                },
                error: function(xhr) {
                    alert('Error: ' + xhr.responseText);
                }
            });
        }
        
        function collectFormData() {
            const data = {
                report_name: $('input[name="report_name"]').val(),
                base_table: $('#baseTable').val(),
                description: $('textarea[name="description"]').val(),
                is_template: $('#isTemplate').is(':checked') ? 1 : 0,
                columns: [],
                joins: [],
                conditions: [],
                groups: [],
                orders: []
            };
            
            // Collect columns
            $('.column-item').each(function() {
                data.columns.push({
                    column_expression: $(this).find('[name*="column_expression"]').val(),
                    alias: $(this).find('[name*="alias"]').val(),
                    aggregate_function: $(this).find('[name*="aggregate_function"]').val(),
                    data_type: $(this).find('[name*="data_type"]').val()
                });
            });
            
            // Collect joins
            $('.join-item').each(function() {
                data.joins.push({
                    join_type: $(this).find('[name*="join_type"]').val(),
                    table_name: $(this).find('[name*="table_name"]').val(),
                    alias: $(this).find('[name*="alias"]').val(),
                    join_condition: $(this).find('[name*="join_condition"]').val()
                });
            });
            
            // Collect conditions
            $('.condition-item').each(function() {
                data.conditions.push({
                    condition_type: $(this).find('[name*="condition_type"]').val(),
                    condition_expression: $(this).find('[name*="condition_expression"]').val(),
                    operator: $(this).find('[name*="operator"]').val(),
                    is_parameter: $(this).find('[name*="is_parameter"]').is(':checked') ? 1 : 0,
                    parameter_name: $(this).find('[name*="parameter_name"]').val() || null,
                    parameter_default: $(this).find('[name*="parameter_default"]').val() || null
                });
            });
            
            // Collect groups (simple)
            $('.simple-group-item').each(function() {
                data.groups.push({
                    group_type: 'COLUMN',
                    group_expression: $(this).find('[name*="group_expression"]').val()
                });
            });
            
            // Collect groups (complex)
            $('.complex-group-item').each(function() {
                data.groups.push({
                    group_type: $(this).find('[name*="group_type"]').val(),
                    group_expression: $(this).find('[name*="group_expression"]').val(),
                    with_rollup: $(this).find('[name*="with_rollup"]').is(':checked') ? 1 : 0
                });
            });
            
            // Collect orders (simple)
            $('.simple-order-item').each(function() {
                data.orders.push({
                    order_type: 'COLUMN',
                    order_expression: $(this).find('[name*="order_expression"]').val(),
                    direction: $(this).find('[name*="direction"]').val()
                });
            });
            
            // Collect orders (complex)
            $('.complex-order-item').each(function() {
                data.orders.push({
                    order_type: $(this).find('[name*="order_type"]').val(),
                    order_expression: $(this).find('[name*="order_expression"]').val(),
                    direction: $(this).find('[name*="direction"]').val(),
                    nulls_order: $(this).find('[name*="nulls_order"]').val() || null
                });
            });
            
            return data;
        }
        
        // Initialize with existing data counts
        updateStats();
    </script>
</body>
</html>