<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?> - Report Builder</title>
    <!-- Use different CDN providers -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css" rel="stylesheet">
    <style>
        .builder-section {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            background: #f8f9fa;
        }
        .section-header {
            background: #e9ecef;
            padding: 10px;
            margin: -15px -15px 15px -15px;
            border-radius: 5px 5px 0 0;
            font-weight: bold;
        }
        .draggable-item {
            cursor: move;
            padding: 10px;
            margin-bottom: 5px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 3px;
        }
        .CodeMirror {
            border: 1px solid #dee2e6;
            border-radius: 3px;
            height: auto;
            min-height: 100px;
        }
        .sql-preview {
            background: #1e1e1e;
            color: #d4d4d4;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            border: 1px solid #444;
            border-radius: 5px;
            padding: 15px;
            max-height: 400px;
            overflow: auto;
            white-space: pre-wrap;
            word-break: break-all;
            font-size: 14px;
            line-height: 1.4;
        }
        .sql-keyword {
            color: #569cd6;
        }
        .sql-string {
            color: #ce9178;
        }
        .sql-number {
            color: #b5cea8;
        }
        .sql-comment {
            color: #6a9955;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?= site_url('/') ?>">Report Builder</a>
            <div class="navbar-nav">
                <a class="nav-link" href="<?= site_url('reports') ?>">Back to Reports</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4"><?= esc($title) ?></h2>
        
        <form id="reportForm" method="post">
            <input type="hidden" name="id" value="<?= $report['id'] ?? '' ?>">
            
            <!-- Basic Information -->
            <div class="builder-section">
                <div class="section-header">Basic Information</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Report Name *</label>
                            <input type="text" class="form-control" name="report_name" 
                                   value="<?= old('report_name', $report['report_name'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Base Table *</label>
                            <select class="form-select select2-table" name="base_table" required 
                                    onchange="loadTableColumns(this.value)">
                                <option value="">Select a table...</option>
                                <?php foreach ($tables as $table): ?>
                                    <option value="<?= $table ?>" 
                                        <?= ($report['base_table'] ?? '') === $table ? 'selected' : '' ?>>
                                        <?= $table ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="2"><?= old('description', $report['description'] ?? '') ?></textarea>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" 
                           <?= ($report['is_active'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label">Active</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_template" value="1"
                           <?= ($report['is_template'] ?? 0) ? 'checked' : '' ?>>
                    <label class="form-check-label">Save as Template</label>
                </div>
            </div>

            <!-- Columns -->
            <div class="builder-section">
                <div class="section-header d-flex justify-content-between align-items-center">
                    <span>Columns</span>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addColumn()">
                        <i class="fas fa-plus"></i> Add Column
                    </button>
                </div>
                
                <div id="columnsContainer">
                    <?php if (!empty($report['columns'])): ?>
                        <?php foreach ($report['columns'] as $index => $column): ?>
                            <div class="draggable-item column-item" data-index="<?= $index ?>">
                                <div class="row">
                                    <div class="col-md-5">
                                        <label class="form-label">Expression</label>
                                        <textarea class="form-control sql-editor" 
                                                  name="columns[<?= $index ?>][column_expression]"
                                                  rows="2"><?= $column['column_expression'] ?></textarea>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Alias *</label>
                                        <input type="text" class="form-control" 
                                               name="columns[<?= $index ?>][alias]"
                                               value="<?= $column['alias'] ?>" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Data Type</label>
                                        <select class="form-select" 
                                                name="columns[<?= $index ?>][data_type]">
                                            <option value="string" <?= $column['data_type'] === 'string' ? 'selected' : '' ?>>String</option>
                                            <option value="integer" <?= $column['data_type'] === 'integer' ? 'selected' : '' ?>>Integer</option>
                                            <option value="decimal" <?= $column['data_type'] === 'decimal' ? 'selected' : '' ?>>Decimal</option>
                                            <option value="date" <?= $column['data_type'] === 'date' ? 'selected' : '' ?>>Date</option>
                                            <option value="datetime" <?= $column['data_type'] === 'datetime' ? 'selected' : '' ?>>Datetime</option>
                                            <option value="boolean" <?= $column['data_type'] === 'boolean' ? 'selected' : '' ?>>Boolean</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Format</label>
                                        <input type="text" class="form-control" 
                                               name="columns[<?= $index ?>][format_pattern]"
                                               value="<?= $column['format_pattern'] ?? '' ?>"
                                               placeholder="e.g., currency, date:Y-m-d">
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-12">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="columns[<?= $index ?>][is_groupable]" value="1"
                                                   <?= $column['is_groupable'] ? 'checked' : '' ?>>
                                            <label class="form-check-label">Groupable</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="columns[<?= $index ?>][is_sortable]" value="1"
                                                   <?= $column['is_sortable'] ? 'checked' : '' ?>>
                                            <label class="form-check-label">Sortable</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="columns[<?= $index ?>][is_filterable]" value="1"
                                                   <?= $column['is_filterable'] ? 'checked' : '' ?>>
                                            <label class="form-check-label">Filterable</label>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-danger float-end" 
                                                onclick="removeColumn(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            No columns defined. Add your first column.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Joins -->
            <div class="builder-section">
                <div class="section-header d-flex justify-content-between align-items-center">
                    <span>Joins</span>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addJoin()">
                        <i class="fas fa-plus"></i> Add Join
                    </button>
                </div>
                
                <div id="joinsContainer">
                    <?php if (!empty($report['joins'])): ?>
                        <?php foreach ($report['joins'] as $index => $join): ?>
                            <div class="draggable-item join-item" data-index="<?= $index ?>">
                                <div class="row">
                                    <div class="col-md-2">
                                        <label class="form-label">Type</label>
                                        <select class="form-select" name="joins[<?= $index ?>][join_type]">
                                            <option value="INNER" <?= $join['join_type'] === 'INNER' ? 'selected' : '' ?>>INNER</option>
                                            <option value="LEFT" <?= $join['join_type'] === 'LEFT' ? 'selected' : '' ?>>LEFT</option>
                                            <option value="RIGHT" <?= $join['join_type'] === 'RIGHT' ? 'selected' : '' ?>>RIGHT</option>
                                            <option value="FULL" <?= $join['join_type'] === 'FULL' ? 'selected' : '' ?>>FULL</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Table *</label>
                                        <input type="text" class="form-control" 
                                               name="joins[<?= $index ?>][table_name]"
                                               value="<?= $join['table_name'] ?>" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Alias</label>
                                        <input type="text" class="form-control" 
                                               name="joins[<?= $index ?>][table_alias]"
                                               value="<?= $join['table_alias'] ?? '' ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Condition *</label>
                                        <input type="text" class="form-control" 
                                               name="joins[<?= $index ?>][join_condition]"
                                               value="<?= $join['join_condition'] ?>" required
                                               placeholder="e.g., users.id = orders.user_id">
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">Order</label>
                                        <input type="number" class="form-control" 
                                               name="joins[<?= $index ?>][join_order]"
                                               value="<?= $join['join_order'] ?? $index ?>">
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-danger mt-2" 
                                        onclick="removeJoin(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            No joins defined.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Filters -->
            <div class="builder-section">
                <div class="section-header d-flex justify-content-between align-items-center">
                    <span>Filters</span>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addFilter()">
                        <i class="fas fa-plus"></i> Add Filter
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="addExampleFilter()">
                        <i class="fas fa-magic"></i> Add Example
                    </button>
                </div>
                
                <div id="filtersContainer">
                    <?php if (!empty($report['filters'])): ?>
                        <?php foreach ($report['filters'] as $index => $filter): ?>
                            <div class="draggable-item filter-item" data-index="<?= $index ?>">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">Type</label>
                                        <select class="form-select filter-type" 
                                                name="filters[<?= $index ?>][condition_type]"
                                                onchange="toggleFilterFields(this)">
                                            <option value="WHERE" <?= $filter['condition_type'] === 'WHERE' ? 'selected' : '' ?>>WHERE</option>
                                            <option value="HAVING" <?= $filter['condition_type'] === 'HAVING' ? 'selected' : '' ?>>HAVING</option>
                                            <option value="EXISTS" <?= $filter['condition_type'] === 'EXISTS' ? 'selected' : '' ?>>EXISTS</option>
                                            <option value="NOT EXISTS" <?= $filter['condition_type'] === 'NOT EXISTS' ? 'selected' : '' ?>>NOT EXISTS</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">Expression *</label>
                                        <textarea class="form-control sql-editor" 
                                                  name="filters[<?= $index ?>][condition_expression]"
                                                  rows="2"><?= $filter['condition_expression'] ?></textarea>
                                    </div>
                                    <div class="col-md-4 filter-param-fields" 
                                         style="<?= in_array($filter['condition_type'], ['WHERE', 'HAVING']) ? '' : 'display:none' ?>">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Parameter Name</label>
                                                <input type="text" class="form-control" 
                                                       name="filters[<?= $index ?>][parameter_name]"
                                                       value="<?= $filter['parameter_name'] ?? '' ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Parameter Type</label>
                                                <select class="form-select" 
                                                        name="filters[<?= $index ?>][parameter_type]">
                                                    <option value="string" <?= ($filter['parameter_type'] ?? 'string') === 'string' ? 'selected' : '' ?>>String</option>
                                                    <option value="integer" <?= ($filter['parameter_type'] ?? '') === 'integer' ? 'selected' : '' ?>>Integer</option>
                                                    <option value="decimal" <?= ($filter['parameter_type'] ?? '') === 'decimal' ? 'selected' : '' ?>>Decimal</option>
                                                    <option value="date" <?= ($filter['parameter_type'] ?? '') === 'date' ? 'selected' : '' ?>>Date</option>
                                                    <option value="datetime" <?= ($filter['parameter_type'] ?? '') === 'datetime' ? 'selected' : '' ?>>Datetime</option>
                                                    <option value="boolean" <?= ($filter['parameter_type'] ?? '') === 'boolean' ? 'selected' : '' ?>>Boolean</option>
                                                    <option value="array" <?= ($filter['parameter_type'] ?? '') === 'array' ? 'selected' : '' ?>>Array</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="filters[<?= $index ?>][is_required]" value="1"
                                                           <?= ($filter['is_required'] ?? 0) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Required</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-12">
                                        <small class="text-muted">
                                            Use <code>:value</code> or <code>:param_name</code> as placeholders
                                        </small>
                                        <button type="button" class="btn btn-sm btn-danger float-end" 
                                                onclick="removeFilter(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            No filters defined.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Groups and Orders -->
            <div class="row">
                <div class="col-md-6">
                    <div class="builder-section">
                        <div class="section-header">Group By</div>
                        <div id="groupsContainer">
                            <?php if (!empty($report['groups'])): ?>
                                <?php foreach ($report['groups'] as $index => $group): ?>
                                    <div class="draggable-item group-item" data-index="<?= $index ?>">
                                        <div class="input-group">
                                            <input type="text" class="form-control" 
                                                   name="groups[<?= $index ?>][column_alias]"
                                                   value="<?= $group['column_alias'] ?>"
                                                   placeholder="Column alias">
                                            <input type="number" class="form-control" style="width: 80px;"
                                                   name="groups[<?= $index ?>][group_order]"
                                                   value="<?= $group['group_order'] ?? $index ?>">
                                            <button class="btn btn-outline-danger" type="button"
                                                    onclick="removeGroup(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" 
                                onclick="addGroup()">
                            <i class="fas fa-plus"></i> Add Group
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="builder-section">
                        <div class="section-header">Order By</div>
                        <div id="ordersContainer">
                            <?php if (!empty($report['orders'])): ?>
                                <?php foreach ($report['orders'] as $index => $order): ?>
                                    <div class="draggable-item order-item" data-index="<?= $index ?>">
                                        <div class="input-group">
                                            <input type="text" class="form-control" 
                                                   name="orders[<?= $index ?>][column_alias]"
                                                   value="<?= $order['column_alias'] ?>"
                                                   placeholder="Column alias">
                                            <select class="form-select" style="width: 100px;"
                                                    name="orders[<?= $index ?>][direction]">
                                                <option value="ASC" <?= $order['direction'] === 'ASC' ? 'selected' : '' ?>>ASC</option>
                                                <option value="DESC" <?= $order['direction'] === 'DESC' ? 'selected' : '' ?>>DESC</option>
                                            </select>
                                            <input type="number" class="form-control" style="width: 80px;"
                                                   name="orders[<?= $index ?>][order_priority]"
                                                   value="<?= $order['order_priority'] ?? $index ?>">
                                            <button class="btn btn-outline-danger" type="button"
                                                    onclick="removeOrder(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" 
                                onclick="addOrder()">
                            <i class="fas fa-plus"></i> Add Order
                        </button>
                    </div>
                </div>
            </div>

            <!-- SQL Preview -->
            <div class="builder-section">
                <div class="section-header d-flex justify-content-between align-items-center">
                    <span>SQL Preview</span>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-success" 
                                onclick="copySql()">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" 
                                onclick="formatSql()">
                            <i class="fas fa-code"></i> Format
                        </button>
                    </div>
                </div>
                <div class="sql-preview" id="sqlPreview">
                    <!-- SQL will be generated here -->
                </div>
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-primary" 
                            onclick="generatePreview()">
                        <i class="fas fa-sync"></i> Generate Preview
                    </button>
                    <small class="text-muted ms-2">
                        Preview updates automatically as you make changes
                    </small>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Save Report
                </button>
                <a href="<?= site_url('reports') ?>" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>

    <!-- Template for new items -->
    <template id="columnTemplate">
        <div class="draggable-item column-item">
            <div class="row">
                <div class="col-md-5">
                    <label class="form-label">Expression</label>
                    <textarea class="form-control sql-editor" name="columns[__INDEX__][column_expression]" rows="2" placeholder="e.g., users.name, CONCAT(first_name, ' ', last_name), COUNT(*)"></textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Alias *</label>
                    <input type="text" class="form-control" name="columns[__INDEX__][alias]" required placeholder="e.g., username, full_name, total_count">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Data Type</label>
                    <select class="form-select" name="columns[__INDEX__][data_type]">
                        <option value="string">String</option>
                        <option value="integer">Integer</option>
                        <option value="decimal">Decimal</option>
                        <option value="date">Date</option>
                        <option value="datetime">Datetime</option>
                        <option value="boolean">Boolean</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Format</label>
                    <input type="text" class="form-control" name="columns[__INDEX__][format_pattern]" placeholder="e.g., currency, date:Y-m-d">
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="columns[__INDEX__][is_groupable]" value="1">
                        <label class="form-check-label">Groupable</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="columns[__INDEX__][is_sortable]" value="1" checked>
                        <label class="form-check-label">Sortable</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="columns[__INDEX__][is_filterable]" value="1">
                        <label class="form-check-label">Filterable</label>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger float-end" onclick="removeColumn(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/sql/sql.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/hint/show-hint.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/hint/sql-hint.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>

    <script>
        let columnIndex = <?= count($report['columns'] ?? []) ?>;
        let joinIndex = <?= count($report['joins'] ?? []) ?>;
        let filterIndex = <?= count($report['filters'] ?? []) ?>;
        let groupIndex = <?= count($report['groups'] ?? []) ?>;
        let orderIndex = <?= count($report['orders'] ?? []) ?>;
        
        // Store all CodeMirror instances
        const codeMirrorInstances = new Map();
        let previewDebounceTimer = null;

        // Initialize Select2
        $(document).ready(function() {
            $('.select2-table').select2();
            
            // Initialize CodeMirror for existing SQL editors
            initAllCodeMirrors();
            
            // Make sections sortable
            initSortable();
            
            // Generate initial preview
            setTimeout(() => generatePreview(), 500);
            
            // Add event listeners for automatic preview updates
            document.addEventListener('input', function(e) {
                if (e.target.matches('input, textarea, select')) {
                    schedulePreviewUpdate();
                }
            });
            
            // Monitor CodeMirror changes
            setInterval(() => {
                let needsUpdate = false;
                codeMirrorInstances.forEach((cm, key) => {
                    if (cm.hasFocus()) {
                        needsUpdate = true;
                    }
                });
                if (needsUpdate) {
                    schedulePreviewUpdate();
                }
            }, 1000);
        });

        function loadTableColumns(table) {
            if (!table) return;
            
            fetch(`<?= site_url('reports/get-table-columns') ?>?table=${table}`)
                .then(response => response.json())
                .then(columns => {
                    console.log('Available columns:', columns);
                    // Could update CodeMirror hint options here
                });
        }

        // Initialize all CodeMirror editors
// Initialize all CodeMirror editors
function initAllCodeMirrors() {
    document.querySelectorAll('.sql-editor').forEach(function(textarea, index) {
        // Get existing value
        const existingValue = textarea.value || '';
        
        const cm = initCodeMirror(textarea);
        codeMirrorInstances.set(`editor-${index}`, cm);
        
        // Set the value from textarea
        if (existingValue) {
            cm.setValue(existingValue);
        }
        
        // Add change handler
        cm.on('change', function() {
            textarea.value = cm.getValue();
            schedulePreviewUpdate();
        });
        
        // Ensure textarea has the value
        textarea.value = cm.getValue();
    });
}

        // Initialize a single CodeMirror instance
        function initCodeMirror(textarea) {
            return CodeMirror.fromTextArea(textarea, {
                mode: 'text/x-sql',
                lineNumbers: true,
                lineWrapping: true,
                extraKeys: {"Ctrl-Space": "autocomplete"},
                hintOptions: {
                    tables: {},
                    completeSingle: false
                },
                gutters: ["CodeMirror-linenumbers"],
                matchBrackets: true,
                autoCloseBrackets: true,
                theme: 'default',
                indentUnit: 2,
                tabSize: 2
            });
        }

        function initSortable() {
            new Sortable(document.getElementById('columnsContainer'), {
                handle: '.draggable-item',
                animation: 150,
                onEnd: function() {
                    schedulePreviewUpdate();
                }
            });
            
            new Sortable(document.getElementById('joinsContainer'), {
                handle: '.draggable-item',
                animation: 150,
                onEnd: function() {
                    schedulePreviewUpdate();
                }
            });
            
            new Sortable(document.getElementById('filtersContainer'), {
                handle: '.draggable-item',
                animation: 150,
                onEnd: function() {
                    schedulePreviewUpdate();
                }
            });
            
            new Sortable(document.getElementById('groupsContainer'), {
                handle: '.draggable-item',
                animation: 150,
                onEnd: function() {
                    schedulePreviewUpdate();
                }
            });
            
            new Sortable(document.getElementById('ordersContainer'), {
                handle: '.draggable-item',
                animation: 150,
                onEnd: function() {
                    schedulePreviewUpdate();
                }
            });
        }

        function schedulePreviewUpdate() {
            if (previewDebounceTimer) {
                clearTimeout(previewDebounceTimer);
            }
            previewDebounceTimer = setTimeout(() => {
                generatePreview();
            }, 500);
        }

        function addColumn() {
    const template = document.getElementById('columnTemplate').innerHTML;
    const html = template.replace(/__INDEX__/g, columnIndex);
    
    const div = document.createElement('div');
    div.innerHTML = html;
    const columnItem = div.firstElementChild;
    columnItem.setAttribute('data-index', columnIndex);
    
    document.getElementById('columnsContainer').appendChild(columnItem);
    
    // Initialize CodeMirror for new textarea
    const textarea = columnItem.querySelector('.sql-editor');
    const cm = initCodeMirror(textarea);
    codeMirrorInstances.set(`column-${columnIndex}`, cm);
    
    // Set initial value and add change handler
    cm.setValue('name'); // Default example
    textarea.value = 'name';
    
    cm.on('change', function() {
        textarea.value = cm.getValue();
        schedulePreviewUpdate();
    });
    
    columnIndex++;
    schedulePreviewUpdate();
}

        function removeColumn(button) {
            const columnItem = button.closest('.column-item');
            const index = columnItem.getAttribute('data-index');
            codeMirrorInstances.delete(`column-${index}`);
            columnItem.remove();
            schedulePreviewUpdate();
        }

        function addJoin() {
            const html = `
                <div class="draggable-item join-item" data-index="${joinIndex}">
                    <div class="row">
                        <div class="col-md-2">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="joins[${joinIndex}][join_type]">
                                <option value="INNER">INNER</option>
                                <option value="LEFT">LEFT</option>
                                <option value="RIGHT">RIGHT</option>
                                <option value="FULL">FULL</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Table *</label>
                            <input type="text" class="form-control" name="joins[${joinIndex}][table_name]" required
                                   placeholder="e.g., orders, users">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Alias</label>
                            <input type="text" class="form-control" name="joins[${joinIndex}][table_alias]"
                                   placeholder="e.g., o, u">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Condition *</label>
                            <input type="text" class="form-control" name="joins[${joinIndex}][join_condition]" required
                                   placeholder="e.g., users.id = orders.user_id">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Order</label>
                            <input type="number" class="form-control" name="joins[${joinIndex}][join_order]" value="${joinIndex}">
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeJoin(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            
            document.getElementById('joinsContainer').insertAdjacentHTML('beforeend', html);
            joinIndex++;
            schedulePreviewUpdate();
        }

        function removeJoin(button) {
            button.closest('.join-item').remove();
            schedulePreviewUpdate();
        }

        function addFilter() {
            const html = `
                <div class="draggable-item filter-item" data-index="${filterIndex}">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select class="form-select filter-type" name="filters[${filterIndex}][condition_type]"
                                    onchange="toggleFilterFields(this)">
                                <option value="WHERE">WHERE</option>
                                <option value="HAVING">HAVING</option>
                                <option value="EXISTS">EXISTS</option>
                                <option value="NOT EXISTS">NOT EXISTS</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Expression *</label>
                            <textarea class="form-control sql-editor" name="filters[${filterIndex}][condition_expression]" 
                                    rows="2" placeholder="id = :id OR status = :status OR created_at >= :start_date"></textarea>
                        </div>
                        <div class="col-md-4 filter-param-fields">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Parameter Name</label>
                                    <input type="text" class="form-control" 
                                        name="filters[${filterIndex}][parameter_name]"
                                        placeholder="e.g., id, status, start_date">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Parameter Type</label>
                                    <select class="form-select" 
                                            name="filters[${filterIndex}][parameter_type]">
                                        <option value="string">String</option>
                                        <option value="integer">Integer</option>
                                        <option value="decimal">Decimal</option>
                                        <option value="date">Date</option>
                                        <option value="datetime">Datetime</option>
                                        <option value="boolean">Boolean</option>
                                        <option value="array">Array</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                            name="filters[${filterIndex}][is_required]" value="1">
                                        <label class="form-check-label">Required</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <small class="text-muted">
                                Examples: <code>id = :id</code>, <code>status IN (:status)</code>, <code>created_at >= :start_date</code>
                            </small>
                            <button type="button" class="btn btn-sm btn-danger float-end" onclick="removeFilter(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
    
            const filtersContainer = document.getElementById('filtersContainer');
            filtersContainer.insertAdjacentHTML('beforeend', html);
            
            // Initialize CodeMirror for the new textarea
            const textarea = filtersContainer.querySelector(`[name="filters[${filterIndex}][condition_expression]"]`);
            const cm = initCodeMirror(textarea);
            codeMirrorInstances.set(`filter-${filterIndex}`, cm);
            
            cm.on('change', function() {
                schedulePreviewUpdate();
            });
            
            filterIndex++;
            schedulePreviewUpdate();
        }

        function addExampleFilter() {
            addFilter();
            
            // Set example values after a short delay
            setTimeout(() => {
                const lastFilter = document.querySelector('.filter-item:last-child');
                if (lastFilter) {
                    const typeSelect = lastFilter.querySelector('.filter-type');
                    const expressionTextarea = lastFilter.querySelector('.sql-editor');
                    const paramNameInput = lastFilter.querySelector('[name*="[parameter_name]"]');
                    const paramTypeSelect = lastFilter.querySelector('[name*="[parameter_type]"]');
                    
                    typeSelect.value = 'WHERE';
                    const cm = codeMirrorInstances.get(`filter-${filterIndex - 1}`);
                    if (cm) {
                        cm.setValue('users.status = :status AND users.created_at >= :start_date');
                    }
                    paramNameInput.value = 'status';
                    paramTypeSelect.value = 'string';
                    
                    toggleFilterFields(typeSelect);
                    schedulePreviewUpdate();
                }
            }, 100);
        }

        function toggleFilterFields(select) {
            const parent = select.closest('.filter-item');
            const paramFields = parent.querySelector('.filter-param-fields');
            
            if (select.value === 'WHERE' || select.value === 'HAVING') {
                paramFields.style.display = 'block';
            } else {
                paramFields.style.display = 'none';
            }
        }

        function removeFilter(button) {
            const filterItem = button.closest('.filter-item');
            const index = filterItem.getAttribute('data-index');
            codeMirrorInstances.delete(`filter-${index}`);
            filterItem.remove();
            schedulePreviewUpdate();
        }

        function addGroup() {
            const html = `
                <div class="draggable-item group-item" data-index="${groupIndex}">
                    <div class="input-group">
                        <input type="text" class="form-control" name="groups[${groupIndex}][column_alias]"
                               placeholder="Column alias" oninput="schedulePreviewUpdate()">
                        <input type="number" class="form-control" style="width: 80px;"
                               name="groups[${groupIndex}][group_order]" value="${groupIndex}" oninput="schedulePreviewUpdate()">
                        <button class="btn btn-outline-danger" type="button" onclick="removeGroup(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            document.getElementById('groupsContainer').insertAdjacentHTML('beforeend', html);
            groupIndex++;
            schedulePreviewUpdate();
        }

        function removeGroup(button) {
            button.closest('.group-item').remove();
            schedulePreviewUpdate();
        }

        function addOrder() {
            const html = `
                <div class="draggable-item order-item" data-index="${orderIndex}">
                    <div class="input-group">
                        <input type="text" class="form-control" name="orders[${orderIndex}][column_alias]"
                               placeholder="Column alias" oninput="schedulePreviewUpdate()">
                        <select class="form-select" style="width: 100px;" name="orders[${orderIndex}][direction]"
                                onchange="schedulePreviewUpdate()">
                            <option value="ASC">ASC</option>
                            <option value="DESC">DESC</option>
                        </select>
                        <input type="number" class="form-control" style="width: 80px;"
                               name="orders[${orderIndex}][order_priority]" value="${orderIndex}" oninput="schedulePreviewUpdate()">
                        <button class="btn btn-outline-danger" type="button" onclick="removeOrder(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            document.getElementById('ordersContainer').insertAdjacentHTML('beforeend', html);
            orderIndex++;
            schedulePreviewUpdate();
        }

        function removeOrder(button) {
            button.closest('.order-item').remove();
            schedulePreviewUpdate();
        }

        // Helper function to get value from CodeMirror editor
        function getCodeMirrorValue(parent, selector) {
            const textarea = parent.querySelector(selector);
            if (!textarea) return '';
            
            // Try to get from CodeMirror instance
            const cmKey = Array.from(codeMirrorInstances.keys()).find(key => 
                codeMirrorInstances.get(key)?.display.input.textarea === textarea
            );
            
            if (cmKey && codeMirrorInstances.has(cmKey)) {
                return codeMirrorInstances.get(cmKey).getValue();
            }
            
            // Fallback to textarea value
            return textarea.value;
        }



        // Helper function to ensure expression is a valid condition
        function validateConditionExpression(expression, hasParameter) {
            if (!expression.trim()) return null;
            
            let condition = expression.trim();
            
            // If it's just a column name without an operator, make it a basic equality
            if (!hasParameter && !condition.match(/[=<>!]|IN|LIKE|BETWEEN|IS|NOT/i) && 
                !condition.match(/^\s*\(.*\)\s*$/)) {
                // It's likely just a column name, so make it column = value
                condition = `${condition} = :value`;
            }
            
            return condition;
        }


        // Helper function to extract SQL from form
        // Helper function to replace parameter placeholders with example values
        // Improved parameter placeholder replacement
        function replaceParameterPlaceholder(expression, paramName, paramType) {
            if (!expression || !paramName) return expression;
            
            let exampleValue = '';
            
            switch(paramType.toLowerCase()) {
                case 'integer':
                    exampleValue = '1';
                    break;
                case 'decimal':
                case 'float':
                    exampleValue = '1.5';
                    break;
                case 'string':
                    exampleValue = "'example'";
                    break;
                case 'date':
                    exampleValue = "'2024-01-01'";
                    break;
                case 'datetime':
                    exampleValue = "'2024-01-01 12:00:00'";
                    break;
                case 'boolean':
                    exampleValue = 'TRUE';
                    break;
                case 'array':
                    exampleValue = "(1, 2, 3)";
                    break;
                default:
                    exampleValue = "'value'";
            }
            
            // Replace all occurrences of :paramName (case-insensitive)
            let result = expression;
            const regex = new RegExp(`:${paramName}\\b`, 'gi');
            result = result.replace(regex, exampleValue);
            
            // Also replace generic :value if no specific param found
            if (!result.includes(exampleValue) && result.includes(':value')) {
                result = result.replace(/:value\b/gi, exampleValue);
            }
            
            return result;
        }
        
        // Helper function to ensure expression is a valid condition
        function validateConditionExpression(expression, hasParameter) {
            if (!expression.trim()) return null;
            
            let condition = expression.trim();
            
            // If it's just a column name without an operator, make it a basic equality
            if (!hasParameter && !condition.match(/[=<>!]|IN|LIKE|BETWEEN|IS|NOT/i) && 
                !condition.match(/^\s*\(.*\)\s*$/)) {
                // It's likely just a column name, so make it column = value
                condition = `${condition} = :value`;
            }
            
            return condition;
        }

        // Helper function to extract SQL from form
        function getSqlFromForm() {
            try {
                const form = document.getElementById('reportForm');
                const baseTable = form.querySelector('[name="base_table"]')?.value;
                
                if (!baseTable) {
                    return '-- Please select a base table first';
                }
                
                // Get columns - FIXED: Always check CodeMirror first
                const columns = [];
                document.querySelectorAll('.column-item').forEach((item, index) => {
                    // Try to get from CodeMirror
                    let expression = '';
                    const textarea = item.querySelector('[name*="[column_expression]"]');
                    
                    // Check if there's a CodeMirror instance
                    if (textarea && textarea.nextElementSibling && 
                        textarea.nextElementSibling.classList.contains('CodeMirror')) {
                        const cm = textarea.nextElementSibling.CodeMirror;
                        if (cm) {
                            expression = cm.getValue();
                        }
                    }
                    
                    // If not found in CodeMirror, try textarea value
                    if (!expression && textarea) {
                        expression = textarea.value;
                    }
                    
                    const alias = item.querySelector('[name*="[alias]"]')?.value || `col_${index}`;
                    
                    if (expression && expression.trim()) {
                        const cleanExpression = expression.trim();
                        columns.push(`${cleanExpression} AS \`${alias}\``);
                    }
                });
                
                let sql = `SELECT `;
                sql += columns.length > 0 ? columns.join(', ') : '*';
                sql += `\nFROM \`${baseTable}\``;
                
                // Get joins
                const joins = [];
                document.querySelectorAll('.join-item').forEach(item => {
                    const joinType = item.querySelector('[name*="[join_type]"]')?.value || 'INNER';
                    const table = item.querySelector('[name*="[table_name]"]')?.value || '';
                    const alias = item.querySelector('[name*="[table_alias]"]')?.value || '';
                    const condition = item.querySelector('[name*="[join_condition]"]')?.value || '';
                    
                    if (table && condition) {
                        let joinClause = `\n${joinType} JOIN \`${table}\``;
                        if (alias.trim()) {
                            joinClause += ` AS \`${alias}\``;
                        }
                        joinClause += ` ON ${condition}`;
                        joins.push(joinClause);
                    }
                });
                
                if (joins.length > 0) {
                    sql += joins.join(' ');
                }
                
                // Process filters - FIXED: Better parameter handling
                const whereConditions = [];
                const havingConditions = [];
                const existsConditions = [];
                
                document.querySelectorAll('.filter-item').forEach(item => {
                    const type = item.querySelector('[name*="[condition_type]"]')?.value || '';
                    
                    // Get expression from CodeMirror or textarea
                    let expression = '';
                    const textarea = item.querySelector('[name*="[condition_expression]"]');
                    
                    if (textarea && textarea.nextElementSibling && 
                        textarea.nextElementSibling.classList.contains('CodeMirror')) {
                        const cm = textarea.nextElementSibling.CodeMirror;
                        if (cm) {
                            expression = cm.getValue();
                        }
                    }
                    
                    if (!expression && textarea) {
                        expression = textarea.value;
                    }
                    
                    if (!expression || !expression.trim()) return;
                    
                    const paramName = item.querySelector('[name*="[parameter_name]"]')?.value || '';
                    const paramType = item.querySelector('[name*="[parameter_type]"]')?.value || 'string';
                    
                    let condition = expression.trim();
                    
                    // FIX: If it's just a column name, make it a proper condition
                    if (type === 'WHERE' || type === 'HAVING') {
                        // Check if it's just a simple column name
                        if (/^\s*[a-zA-Z_][a-zA-Z0-9_]*\s*$/.test(condition)) {
                            // It's just a column name, add = :value
                            condition = `${condition} = :value`;
                        }
                        
                        // Handle parameter placeholders
                        if (paramName) {
                            // Replace :paramName with example value
                            condition = replaceParameterPlaceholder(condition, paramName, paramType);
                        } else if (condition.includes(':value')) {
                            // If no param name but has :value, use default
                            condition = replaceParameterPlaceholder(condition, 'value', paramType);
                        }
                    }
                    
                    switch(type) {
                        case 'WHERE':
                            whereConditions.push(condition);
                            break;
                        case 'HAVING':
                            havingConditions.push(condition);
                            break;
                        case 'EXISTS':
                            existsConditions.push(`EXISTS (${condition})`);
                            break;
                        case 'NOT EXISTS':
                            existsConditions.push(`NOT EXISTS (${condition})`);
                            break;
                    }
                });
                
                // Build WHERE clause
                const allWhereConditions = [...whereConditions, ...existsConditions];
                if (allWhereConditions.length > 0) {
                    sql += `\nWHERE ` + allWhereConditions.join('\n  AND ');
                }
                
                // Get GROUP BY
                const groups = [];
                document.querySelectorAll('.group-item').forEach(item => {
                    const column = item.querySelector('[name*="[column_alias]"]')?.value || '';
                    if (column.trim()) {
                        groups.push(`\`${column.trim()}\``);
                    }
                });
                
                if (groups.length > 0) {
                    sql += `\nGROUP BY ` + groups.join(', ');
                }
                
                // Build HAVING clause
                if (havingConditions.length > 0) {
                    sql += `\nHAVING ` + havingConditions.join('\n  AND ');
                }
                
                // Get ORDER BY
                const orders = [];
                document.querySelectorAll('.order-item').forEach(item => {
                    const column = item.querySelector('[name*="[column_alias]"]')?.value || '';
                    const direction = item.querySelector('[name*="[direction]"]')?.value || 'ASC';
                    
                    if (column.trim()) {
                        orders.push(`\`${column.trim()}\` ${direction}`);
                    }
                });
                
                if (orders.length > 0) {
                    sql += `\nORDER BY ` + orders.join(', ');
                }
                
                return sql;
                
            } catch (error) {
                console.error('Error generating SQL:', error);
                return `-- Error generating SQL: ${error.message}`;
            }
        }

        function generatePreview() {
            try {
                const sql = getSqlFromForm();
                const preview = document.getElementById('sqlPreview');
                
                // Format and highlight SQL
                const formattedSql = formatSqlSyntax(sql);
                preview.innerHTML = formattedSql || '-- No SQL generated';
                
            } catch (error) {
                document.getElementById('sqlPreview').textContent = `-- Error: ${error.message}`;
            }
        }

        function formatSqlSyntax(sql) {
            // SQL keywords to highlight
            const keywords = [
                'SELECT', 'FROM', 'WHERE', 'JOIN', 'INNER', 'LEFT', 'RIGHT', 'OUTER', 
                'ON', 'GROUP BY', 'HAVING', 'ORDER BY', 'ASC', 'DESC', 'AS', 'AND', 
                'OR', 'NOT', 'IN', 'BETWEEN', 'LIKE', 'IS', 'NULL', 'EXISTS', 'CASE',
                'WHEN', 'THEN', 'ELSE', 'END', 'COUNT', 'SUM', 'AVG', 'MIN', 'MAX',
                'DISTINCT', 'COALESCE', 'CONCAT', 'DATE_FORMAT', 'TIMESTAMPDIFF'
            ];
            
            // Escape HTML and add syntax highlighting
            let highlighted = sql
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
            
            // Highlight keywords
            keywords.forEach(keyword => {
                const regex = new RegExp(`\\b${keyword}\\b`, 'gi');
                highlighted = highlighted.replace(regex, `<span class="sql-keyword">$&</span>`);
            });
            
            // Highlight strings (single quoted)
            highlighted = highlighted.replace(/'[^']*'/g, '<span class="sql-string">$&</span>');
            
            // Highlight numbers
            highlighted = highlighted.replace(/\b\d+(\.\d+)?\b/g, '<span class="sql-number">$&</span>');
            
            // Highlight comments
            highlighted = highlighted.replace(/--.*$/gm, '<span class="sql-comment">$&</span>');
            
            return highlighted;
        }

        function formatSql() {
            const preview = document.getElementById('sqlPreview');
            const currentSql = preview.textContent;
            
            // Simple SQL formatter
            const formatted = currentSql
                .replace(/\b(SELECT|FROM|WHERE|JOIN|INNER|LEFT|RIGHT|OUTER|ON|GROUP BY|HAVING|ORDER BY|AND|OR)\b/gi, '\n$1')
                .replace(/,/g, ',\n  ')
                .replace(/\(/g, '(\n  ')
                .replace(/\)/g, '\n)');
            
            preview.innerHTML = formatSqlSyntax(formatted);
        }

        function copySql() {
            const preview = document.getElementById('sqlPreview');
            const sqlText = preview.textContent;
            
            navigator.clipboard.writeText(sqlText).then(() => {
                const btn = event.target.closest('button');
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                }, 2000);
            }).catch(err => {
                alert('Failed to copy SQL: ' + err);
            });
        }

        function validateSqlWithServer(sql) {
            // Optional: Send to server for validation
            fetch('<?= site_url("api/sql/test-expression") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({expression: sql})
            })
            .then(response => response.json())
            .then(data => {
                console.log('Validation result:', data);
            })
            .catch(error => {
                console.log('Validation failed:', error);
            });
        }
    </script>
</body>
</html>