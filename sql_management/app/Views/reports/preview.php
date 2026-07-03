<?= $this->extend('template') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-eye"></i> Preview: <?= esc($report['report_name']) ?>
                    </h4>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-download"></i> Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item export-link" href="#" data-format="csv">CSV</a></li>
                            <li><a class="dropdown-item export-link" href="#" data-format="excel">Excel</a></li>
                            <li><a class="dropdown-item export-link" href="#" data-format="json">JSON</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Report Description -->
                    <?php if ($report['description']): ?>
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle"></i> 
                        <?= esc($report['description']) ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Filter Form -->
                    <div class="filter-section mb-4">
                        <h5 class="mb-3">
                            <i class="fas fa-filter"></i> Filter Results
                            <?php if (!empty($where_conditions)): ?>
                                <small class="text-muted">(Extracted from query)</small>
                            <?php endif; ?>
                        </h5>
                        <form method="post" action="<?= site_url('reports/preview/' . $report['id']) ?>" id="filterForm">
                            <?= csrf_field() ?>

                            <input type="hidden" name="filters[report_id]" value="<?= $report['id'] ?>">
                            
                            <!-- WHERE Conditions Filters -->
<!-- WHERE Conditions Filters -->
<?php if (!empty($where_conditions)): ?>
    <div class="mb-4">
        <h6 class="border-bottom pb-2">WHERE Conditions</h6>
        <div class="row g-3">
            <?php 
            $displayConditions = [];
            foreach ($where_conditions as $condition) {
                // Skip conditions that are not actually WHERE conditions
                if (isset($condition['column']) && 
                    (stripos($condition['column'], 'ORDER BY') !== false || 
                     stripos($condition['column'], 'GROUP BY') !== false)) {
                    continue;
                }
                
                // Skip NOT EXISTS conditions from display
                if ($condition['type'] === 'NOT_EXISTS') {
                    continue;
                }
                
                // Skip conditions without column or invalid
                if (empty($condition['column']) || $condition['column'] === 'Unknown') {
                    continue;
                }
                
                $displayConditions[] = $condition;
            }
            
            if (empty($displayConditions)): 
            ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        No editable WHERE conditions found in query.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($displayConditions as $index => $condition): ?>
                    <div class="col-md-6">
                        <div class="card mb-2">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <?= esc($condition['column']) ?>
                                    <small class="text-muted">(<?= esc($condition['type']) ?>)</small>
                                </h6>
                                
                                <?php if ($condition['type'] === 'IN' && isset($condition['values'])): ?>
                                    <div class="mb-2">
                                        <label class="form-label small">Select values:</label>
                                        <select name="filters[<?= esc($condition['column']) ?>][]" 
                                                class="form-control" 
                                                multiple="multiple">
                                            <?php foreach ($condition['values'] as $value): ?>
                                                <option value="<?= esc($value) ?>"
                                                    <?= (isset($applied_filters[$condition['column']]) && in_array($value, (array)$applied_filters[$condition['column']])) ? 'selected' : '' ?>>
                                                    <?= esc($value) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                <?php elseif ($condition['type'] === 'BETWEEN'): ?>
                                    <div class="mb-2">
                                        <label class="form-label small">Date Range:</label>
                                        <div class="row g-2">
                                            <div class="col">
                                                <input type="date" 
                                                       class="form-control" 
                                                       name="filters[FROM_<?= esc($condition['column']) ?>]" 
                                                       placeholder="From"
                                                       value="<?= isset($applied_filters['FROM_' . $condition['column']]) ? esc($applied_filters['FROM_' . $condition['column']]) : esc($condition['from'] ?? '') ?>">
                                            </div>
                                            <div class="col">
                                                <input type="date" 
                                                       class="form-control" 
                                                       name="filters[TO_<?= esc($condition['column']) ?>]" 
                                                       placeholder="To"
                                                       value="<?= isset($applied_filters['TO_' . $condition['column']]) ? esc($applied_filters['TO_' . $condition['column']]) : esc($condition['to'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                <?php elseif ($condition['type'] === 'LIKE'): ?>
                                    <div class="mb-2">
                                        <label class="form-label small">Search for:</label>
                                        <input type="text" 
                                               class="form-control" 
                                               name="filters[LIKE_<?= esc($condition['column']) ?>]" 
                                               placeholder="Enter search text"
                                               value="<?= isset($applied_filters['LIKE_' . $condition['column']]) ? esc($applied_filters['LIKE_' . $condition['column']]) : esc($condition['value'] ?? '') ?>">
                                    </div>
                                    
                                <?php elseif ($condition['type'] === 'IS_NULL'): ?>
                                    <div class="mb-2">
                                        <label class="form-label small">NULL Check:</label>
                                        <select class="form-control" name="filters[<?= esc($condition['column']) ?>]">
                                            <option value="">-- Select --</option>
                                            <option value="NULL" <?= (isset($applied_filters[$condition['column']]) && $applied_filters[$condition['column']] == 'NULL') ? 'selected' : '' ?>>IS NULL</option>
                                            <option value="NOT NULL" <?= (isset($applied_filters[$condition['column']]) && $applied_filters[$condition['column']] == 'NOT NULL') ? 'selected' : '' ?>>IS NOT NULL</option>
                                        </select>
                                    </div>
                                    
                                <?php elseif ($condition['type'] === 'COMPARISON' || $condition['type'] === 'FUNCTION_COMPARISON'): ?>
                                    <div class="mb-2">
                                        <div class="row g-2">
                                            <div class="col-3">
                                                <select class="form-control" name="filters[OP_<?= esc($condition['column']) ?>]">
                                                    <option value="=" <?= (isset($applied_filters['OP_' . $condition['column']]) && $applied_filters['OP_' . $condition['column']] == '=') ? 'selected' : '' ?>>=</option>
                                                    <option value="!=" <?= (isset($applied_filters['OP_' . $condition['column']]) && $applied_filters['OP_' . $condition['column']] == '!=') ? 'selected' : '' ?>>!=</option>
                                                    <option value="<" <?= (isset($applied_filters['OP_' . $condition['column']]) && $applied_filters['OP_' . $condition['column']] == '<') ? 'selected' : '' ?>>&lt;</option>
                                                    <option value=">" <?= (isset($applied_filters['OP_' . $condition['column']]) && $applied_filters['OP_' . $condition['column']] == '>') ? 'selected' : '' ?>>&gt;</option>
                                                    <option value="<=" <?= (isset($applied_filters['OP_' . $condition['column']]) && $applied_filters['OP_' . $condition['column']] == '<=') ? 'selected' : '' ?>>&lt;=</option>
                                                    <option value=">=" <?= (isset($applied_filters['OP_' . $condition['column']]) && $applied_filters['OP_' . $condition['column']] == '>=') ? 'selected' : '' ?>>&gt;=</option>
                                                </select>
                                            </div>
                                            <div class="col-9">
                                                <input type="text" 
                                                       class="form-control" 
                                                       name="filters[<?= esc($condition['column']) ?>]" 
                                                       value="<?= isset($applied_filters[$condition['column']]) ? esc($applied_filters[$condition['column']]) : esc($condition['value'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                <?php else: ?>
                                    <div class="mb-2">
                                        <label class="form-label small">Value:</label>
                                        <input type="text" 
                                               class="form-control" 
                                               name="filters[<?= esc($condition['column']) ?>]" 
                                               value="<?= isset($applied_filters[$condition['column']]) ? esc($applied_filters[$condition['column']]) : '' ?>">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="small text-muted mt-2">
                                    <strong>Original condition:</strong><br>
                                    <code><?= esc($condition['condition']) ?></code>
                                    <?php if (isset($condition['operator']) && $condition['operator']): ?>
                                        <br><strong>Operator:</strong> <?= esc($condition['operator']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
                            
                            <!-- Additional Filters Section -->
                            <div class="mb-4">
                                <h6 class="border-bottom pb-2">Additional Filters</h6>
                                
<!-- GROUP BY Dynamic Control -->
<div class="row mb-3">
            <div class="col-12">
                <label class="form-label">GROUP BY</label>
                <div class="d-flex align-items-center">
                    <select class="form-control me-2" id="group_by_select" style="max-width: 300px;">
                        <option value="">-- Select Column --</option>
                        <?php 
                        $groupByOptions = [];
                        if (!empty($group_by)) {
                            foreach ($group_by as $group) {
                                $original = $group['original'] ?? $group;
                                if (!in_array($original, $groupByOptions)) {
                                    $groupByOptions[] = $original;
                                }
                            }
                        }
                        foreach ($selected_columns as $col) {
                            $original = $col['original'];
                            if (!in_array($original, $groupByOptions)) {
                                $groupByOptions[] = $original;
                            }
                        }
                        
                        foreach ($groupByOptions as $option): 
                        ?>
                            <option value="<?= esc($option) ?>"
                                <?= (isset($applied_filters['group_by']) && in_array($option, (array)$applied_filters['group_by'])) ? 'selected' : '' ?>>
                                <?= esc($option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addGroupBy()">
                        <i class="fas fa-plus"></i> Add
                    </button>
                </div>
                
                <!-- Selected GROUP BY columns -->
                <div id="selected_group_by" class="mt-2">
    <?php 
    $displayGroupBy = [];
    if (isset($applied_filters['group_by']) && !empty($applied_filters['group_by'])) {
        $displayGroupBy = (array)$applied_filters['group_by'];
    } elseif (!empty($group_by)) {
        foreach ($group_by as $group) {
            $displayGroupBy[] = $group['original'] ?? $group;
        }
    }
    
    foreach ($displayGroupBy as $index => $group): 
        if (!empty($group)):
    ?>
        <span class="badge bg-info me-1 mb-1" id="group-badge-<?= $index ?>">
            <i class="fas fa-layer-group"></i> 
            <?= esc($group) ?>
            <input type="hidden" name="group_by[<?= $index ?>]" value="<?= esc($group) ?>">
            <button type="button" class="btn-close btn-close-white ms-1" 
                    onclick="removeGroupBy('group-badge-<?= $index ?>')"></button>
        </span>
    <?php 
        endif;
    endforeach; 
    ?>
</div>
                
                <small class="text-muted">Select columns from the query to group by</small>
            </div>
        </div>

<!-- ORDER BY Dynamic Control -->
<!-- ORDER BY Dynamic Control -->
<div class="row mb-4">
            <div class="col-12">
                <label class="form-label">ORDER BY</label>
                
                <!-- ORDER BY items container -->
                <div id="order_by_container" class="mb-2">
                    <?php 
                    // Get all available column options
                    $columnOptions = [];
                    if (!empty($order_by)) {
                        foreach ($order_by as $order) {
                            $original = $order['original'] ?? $order['column'];
                            if (!in_array($original, $columnOptions)) {
                                $columnOptions[] = $original;
                            }
                        }
                    }
                    foreach ($selected_columns as $col) {
                        $original = $col['original'];
                        if (!in_array($original, $columnOptions)) {
                            $columnOptions[] = $original;
                        }
                    }
                    
                    // Prepare ORDER BY data
                    $orderByData = [];
                    if (isset($applied_filters['order_by']) && !empty($applied_filters['order_by'])) {
                        $orderByData = $applied_filters['order_by'];
                    } elseif (!empty($order_by)) {
                        foreach ($order_by as $index => $order) {
                            $orderByData[] = [
                                'column' => $order['original'] ?? $order['column'],
                                'direction' => $order['direction']
                            ];
                        }
                    }
                    
                    if (empty($orderByData)) {
                        $orderByData[] = ['column' => '', 'direction' => 'ASC'];
                    }
                    ?>
                    
                    <?php foreach ($orderByData as $index => $order): ?>
                        <div class="row g-2 mb-2 align-items-center order-by-item" id="order-by-<?= $index ?>">
                            <div class="col-md-5">
                                <select class="form-control order-by-column" name="order_by[<?= $index ?>][column]" onchange="updateOrderBySelect()">
                                    <option value="">-- Select Column --</option>
                                    <?php foreach ($columnOptions as $option): ?>
                                        <option value="<?= esc($option) ?>"
                                            <?= (isset($order['column']) && $order['column'] == $option) ? 'selected' : '' ?>>
                                            <?= esc($option) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-control" name="order_by[<?= $index ?>][direction]">
                                    <option value="ASC" <?= (isset($order['direction']) && $order['direction'] == 'ASC') ? 'selected' : '' ?>>ASC</option>
                                    <option value="DESC" <?= (isset($order['direction']) && $order['direction'] == 'DESC') ? 'selected' : '' ?>>DESC</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <?php if ($index > 0): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeOrderBy(<?= $index ?>)">
                                        <i class="fas fa-times"></i> Remove
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Add ORDER BY button -->
                <div class="row">
                    <div class="col-12">
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="addOrderBy()">
                            <i class="fas fa-plus"></i> Add ORDER BY
                        </button>
                        <small class="text-muted ms-2">Select columns from the query to order by</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
                            </div>
                            
                            <!-- Filter Actions -->
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i> Apply Filters
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                                                <i class="fas fa-redo"></i> Reset to Default
                                            </button>
                                        </div>
                                        <div>
                                            <a href="<?= site_url('reports') ?>" class="btn btn-outline-secondary me-2">
                                                <i class="fas fa-arrow-left"></i> Back
                                            </a>
                                            <a href="<?= site_url('reports/edit/' . $report['id']) ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-edit"></i> Edit Report
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Results Section -->
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>
                                <i class="fas fa-table"></i> Results 
                                <?php if (!empty($results)): ?>
                                <span class="badge bg-secondary"><?= count($results) ?> records</span>
                                <?php endif; ?>
                            </h5>
                            <?php if (!empty($results)): ?>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleColumnVisibility()">
                                    <i class="fas fa-eye"></i> Toggle Columns
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (empty($results)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> No results found for this query.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover" id="resultsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <?php foreach ($columns as $column): ?>
                                            <th class="column-toggle" style="cursor: pointer;" data-column="<?= esc($column) ?>">
                                                <?= esc($column) ?>
                                                <i class="fas fa-eye text-primary float-end"></i>
                                            </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($results as $row): ?>
                                        <tr>
                                            <?php foreach ($row as $key => $value): ?>
                                            <td class="column-<?= esc($key) ?>">
                                                <?= !empty($value) ? esc($value) : '<span class="text-muted">N/A</span>' ?>
                                            </td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Results Summary -->
                            <div class="alert alert-light mt-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <i class="fas fa-database"></i> 
                                            Query executed successfully. Showing <?= count($results) ?> records.
                                        </small>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <small class="text-muted">
                                            Generated: <?= date('Y-m-d H:i:s') ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between">
                        <a href="<?= site_url('reports') ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Reports
                        </a>
                        <div>
                            <a href="<?= site_url('reports/export/' . $report['id'] . '/csv') ?>" class="btn btn-success">
                                <i class="fas fa-file-csv"></i> Export CSV
                            </a>
                            <a href="<?= site_url('reports/export/' . $report['id'] . '/excel') ?>" class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </a>
                        </div>
                    </div>
                </div> -->
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between">
                        <a href="<?= site_url('reports') ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Reports
                        </a>
                        <div>
                            <button type="button" class="btn btn-success export-link" data-format="csv">
                                <i class="fas fa-file-csv"></i> Export CSV
                            </button>
                            <button type="button" class="btn btn-success export-link" data-format="excel">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Query Details Card -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-code"></i> SQL Query Details
                    </h5>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded"><code class="sql"><?= htmlspecialchars($report['base_query']) ?></code></pre>
                    
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Columns</h6>
                                    <p class="card-text">
                                        <?= !empty($selected_columns) ? count($selected_columns) : 'N/A' ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Conditions</h6>
                                    <p class="card-text">
                                        <?= !empty($where_conditions) ? count($where_conditions) : 'N/A' ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Created</h6>
                                    <p class="card-text">
                                        <?= date('M d, Y', strtotime($report['created_at'])) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

function getCurrentFilters() {
    const formData = new FormData(document.getElementById('filterForm'));
    const filters = {};
    
    // Collect filters from form
    formData.forEach((value, key) => {
        // Handle array inputs (group_by[], order_by[][column], etc.)
        if (key.includes('[') && key.includes(']')) {
            // Parse array keys
            const match = key.match(/^(\w+)\[(\d+)\](\[(\w+)\])?$/);
            if (match) {
                const baseKey = match[1];
                const index = match[2];
                const subKey = match[4];
                
                if (!filters[baseKey]) {
                    filters[baseKey] = [];
                }
                
                if (subKey) {
                    // For order_by[0][column] format
                    if (!filters[baseKey][index]) {
                        filters[baseKey][index] = {};
                    }
                    filters[baseKey][index][subKey] = value;
                } else {
                    // For group_by[0] format
                    filters[baseKey][index] = value;
                }
            } else {
                // Handle filters[OP_username] format
                if (key.startsWith('filters[')) {
                    const filterKey = key.replace('filters[', '').replace(']', '');
                    filters[filterKey] = value;
                }
            }
        } else if (key === 'filters[report_id]') {
            // Handle report_id specially
            filters['report_id'] = value;
        }
    });
    
    return filters;
}

    // Dynamic GROUP BY functions
    // Get column options from PHP
const columnOptions = <?= json_encode($columnOptions) ?>;
let groupByCounter = 0;

function addGroupBy() {
    const select = document.getElementById('group_by_select');
    const column = select.value;
    
    if (!column) {
        alert('Please select a column');
        return;
    }
    
    const container = document.getElementById('selected_group_by');
    const index = container.querySelectorAll('.badge').length;
    
    const badgeHtml = `
        <span class="badge bg-info me-1 mb-1" id="group-badge-${index}">
            <i class="fas fa-layer-group"></i> 
            ${column}
            <input type="hidden" name="group_by[${index}]" value="${column}">
            <button type="button" class="btn-close btn-close-white ms-1" 
                    onclick="removeGroupBy('group-badge-${index}')"></button>
        </span>
    `;
    
    container.insertAdjacentHTML('beforeend', badgeHtml);
    select.value = '';
}

function removeGroupBy(badgeId) {
    const badge = document.getElementById(badgeId);
    if (badge) {
        badge.remove();
    }
}

// Dynamic ORDER BY functions
// Dynamic ORDER BY functions
let orderByCounter = document.querySelectorAll('.order-by-item').length;

function addOrderBy() {
    const container = document.getElementById('order_by_container');
    
    // Get currently selected columns to disable them
    const selectedColumns = new Set();
    document.querySelectorAll('.order-by-column').forEach(select => {
        if (select.value) {
            selectedColumns.add(select.value);
        }
    });
    
    const html = `
        <div class="row g-2 mb-2 align-items-center order-by-item" id="order-by-${orderByCounter}">
            <div class="col-md-5">
                <select class="form-control order-by-column" name="order_by[${orderByCounter}][column]" onchange="updateOrderBySelect()">
                    <option value="">-- Select Column --</option>
                    ${columnOptions.map(col => {
                        const disabled = selectedColumns.has(col) ? 'disabled' : '';
                        return `<option value="${col}" ${disabled}>${col}</option>`;
                    }).join('')}
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-control" name="order_by[${orderByCounter}][direction]">
                    <option value="ASC">ASC</option>
                    <option value="DESC">DESC</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeOrderBy(${orderByCounter})">
                    <i class="fas fa-times"></i> Remove
                </button>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', html);
    orderByCounter++;
}

function removeOrderBy(index) {
    const element = document.getElementById('order-by-' + index);
    if (element) {
        element.remove();
        updateOrderBySelect();
    }
}

// Enhanced reset function
// Enhanced reset function
function resetFilters() {
    // Reset form
    document.getElementById('filterForm').reset();
    
    // Reset GROUP BY
    document.getElementById('group_by_select').value = '';
    const groupByContainer = document.getElementById('selected_group_by');
    groupByContainer.innerHTML = '';
    
    // Reset to original GROUP BY if exists
    <?php if (!empty($group_by)): ?>
        <?php foreach ($group_by as $index => $group): 
            $original = $group['original'] ?? $group;
        ?>
            groupByContainer.innerHTML += `
                <span class="badge bg-info me-1 mb-1" id="group-badge-orig-<?= $index ?>">
                    <i class="fas fa-layer-group"></i> 
                    <?= esc($original) ?>
                    <input type="hidden" name="group_by[]" value="<?= esc($original) ?>">
                    <button type="button" class="btn-close btn-close-white ms-1" 
                            onclick="removeGroupBy('group-badge-orig-<?= $index ?>')"></button>
                </span>
            `;
        <?php endforeach; ?>
    <?php endif; ?>
    
    // Reset ORDER BY
    const orderByContainer = document.getElementById('order_by_container');
    orderByContainer.innerHTML = '';
    
    // Reset to original ORDER BY if exists
    <?php if (!empty($order_by)): ?>
        <?php foreach ($order_by as $index => $order): 
            $original = $order['original'] ?? $order['column'];
        ?>
            orderByContainer.innerHTML += `
                <div class="row g-2 mb-2 align-items-center order-by-item" id="order-by-<?= $index ?>">
                    <div class="col-md-5">
                        <select class="form-control order-by-column" name="order_by[<?= $index ?>][column]" onchange="updateOrderBySelect()">
                            <option value="">-- Select Column --</option>
                            ${columnOptions.map(col => 
                                `<option value="${col}" ${col === '<?= esc($original) ?>' ? 'selected' : ''}>${col}</option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" name="order_by[<?= $index ?>][direction]">
                            <option value="ASC" <?= ($order['direction'] == 'ASC') ? 'selected' : '' ?>>ASC</option>
                            <option value="DESC" <?= ($order['direction'] == 'DESC') ? 'selected' : '' ?>>DESC</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <?php if ($index > 0): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeOrderBy(<?= $index ?>)">
                                <i class="fas fa-times"></i> Remove
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            `;
        <?php endforeach; ?>
    <?php else: ?>
        // Add default ORDER BY
        orderByContainer.innerHTML = `
            <div class="row g-2 mb-2 align-items-center order-by-item" id="order-by-0">
                <div class="col-md-5">
                    <select class="form-control order-by-column" name="order_by[0][column]" onchange="updateOrderBySelect()">
                        <option value="">-- Select Column --</option>
                        ${columnOptions.map(col => 
                            `<option value="${col}">${col}</option>`
                        ).join('')}
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-control" name="order_by[0][direction]">
                        <option value="ASC">ASC</option>
                        <option value="DESC">DESC</option>
                    </select>
                </div>
            </div>
        `;
    <?php endif; ?>
    
    orderByCounter = document.querySelectorAll('.order-by-item').length;
    
    // Update select states
    setTimeout(updateOrderBySelect, 100);
    
    // Reset Select2
    $('.select2').val(null).trigger('change');
}

function toggleColumnVisibility() {
    const columns = document.querySelectorAll('#resultsTable th.column-toggle');
    columns.forEach(th => {
        const columnName = th.getAttribute('data-column');
        const icon = th.querySelector('i');
        const cells = document.querySelectorAll(`.column-${columnName}`);
        
        if (th.classList.contains('hidden')) {
            th.classList.remove('hidden');
            cells.forEach(cell => cell.style.display = 'table-cell');
            icon.className = 'fas fa-eye text-primary float-end';
        } else {
            th.classList.add('hidden');
            cells.forEach(cell => cell.style.display = 'none');
            icon.className = 'fas fa-eye-slash text-muted float-end';
        }
    });
}

// Initialize column toggling
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.export-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const format = this.getAttribute('data-format');
            const reportId = <?= $report['id'] ?>;
            
            // Get current filters
            const filters = getCurrentFilters();
            
            // Build export URL with filters as query parameters
            let exportUrl = '<?= site_url("reports/export/") ?>' + reportId + '/' + format;
            
            // Add filters as query parameters
            const params = new URLSearchParams();
            
            // Flatten filters for URL
            for (const [key, value] of Object.entries(filters)) {
                if (Array.isArray(value)) {
                    // Handle arrays (group_by, order_by)
                    value.forEach((item, index) => {
                        if (typeof item === 'object') {
                            // For order_by objects
                            for (const [subKey, subValue] of Object.entries(item)) {
                                params.append(`${key}[${index}][${subKey}]`, subValue);
                            }
                        } else {
                            // For simple arrays
                            params.append(`${key}[${index}]`, item);
                        }
                    });
                } else {
                    // For simple values
                    params.append(key, value);
                }
            }
            
            // Add CSRF token
            params.append('csrf_test_name', document.querySelector('input[name="csrf_test_name"]').value);
            
            // Open export in new window
            window.open(exportUrl + '?' + params.toString(), '_blank');
        });
    });
    
    // Also update the export buttons in the footer
    document.querySelectorAll('.btn-success').forEach(btn => {
        if (btn.href.includes('/export/')) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                
                const href = this.href;
                const format = href.includes('/csv') ? 'csv' : 
                              href.includes('/excel') ? 'excel' : 'json';
                
                // Get current filters
                const filters = getCurrentFilters();
                
                // Build export URL with filters
                let exportUrl = href.split('?')[0];
                const params = new URLSearchParams();
                
                // Flatten filters for URL
                for (const [key, value] of Object.entries(filters)) {
                    if (Array.isArray(value)) {
                        value.forEach((item, index) => {
                            if (typeof item === 'object') {
                                for (const [subKey, subValue] of Object.entries(item)) {
                                    params.append(`${key}[${index}][${subKey}]`, subValue);
                                }
                            } else {
                                params.append(`${key}[${index}]`, item);
                            }
                        });
                    } else {
                        params.append(key, value);
                    }
                }
                
                // Add CSRF token
                params.append('csrf_test_name', document.querySelector('input[name="csrf_test_name"]').value);
                
                // Open export in new window
                window.open(exportUrl + '?' + params.toString(), '_blank');
            });
        }
    });
    updateOrderBySelect();
    const headers = document.querySelectorAll('#resultsTable th.column-toggle');
    headers.forEach(th => {
        th.addEventListener('click', function() {
            const columnName = this.getAttribute('data-column');
            const icon = this.querySelector('i');
            const cells = document.querySelectorAll(`.column-${columnName}`);
            const tds = document.querySelectorAll(`td.column-${columnName}`);
            
            if (this.classList.contains('hidden')) {
                this.classList.remove('hidden');
                tds.forEach(td => td.style.display = 'table-cell');
                icon.className = 'fas fa-eye text-primary float-end';
            } else {
                this.classList.add('hidden');
                tds.forEach(td => td.style.display = 'none');
                icon.className = 'fas fa-eye-slash text-muted float-end';
            }
        });
    });
});

function updateOrderBySelect() {
    const selects = document.querySelectorAll('.order-by-column');
    const selectedValues = new Set();
    
    // Collect all selected values except empty ones
    selects.forEach(select => {
        if (select.value) {
            selectedValues.add(select.value);
        }
    });
    
    // Update all select options
    selects.forEach(select => {
        const currentValue = select.value;
        const options = select.querySelectorAll('option');
        
        options.forEach(option => {
            if (option.value === '') {
                option.disabled = false;
            } else if (option.value === currentValue) {
                option.disabled = false;
            } else if (selectedValues.has(option.value) && option.value !== currentValue) {
                option.disabled = true;
            } else {
                option.disabled = false;
            }
        });
    });
}


</script>

<style>
.column-toggle.hidden {
    opacity: 0.5;
    background-color: #f8f9fa;
}
</style>
<?= $this->endSection() ?>