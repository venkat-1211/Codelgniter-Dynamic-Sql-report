<?= $this->extend('template') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-edit"></i> Edit Report: <?= esc($report['report_name']) ?>
                    </h4>
                </div>
                <div class="card-body">
                    <form method="post" action="<?= site_url('reports/update/' . $report['id']) ?>">
                        <?= csrf_field() ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="report_name" class="form-label">Report Name *</label>
                                <input type="text" 
                                       class="form-control <?= validation_show_error('report_name') ? 'is-invalid' : '' ?>" 
                                       id="report_name" 
                                       name="report_name" 
                                       value="<?= old('report_name', $report['report_name']) ?>" 
                                       required>
                                <?php if (validation_show_error('report_name')): ?>
                                    <div class="invalid-feedback">
                                        <?= validation_show_error('report_name') ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="2"><?= old('description', $report['description']) ?></textarea>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="base_query" class="form-label">SQL Query *</label>
                                <div class="mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="validateQuery()">
                                        <i class="fas fa-check"></i> Validate SQL
                                    </button>
                                </div>
                                <textarea class="form-control sql-editor <?= validation_show_error('base_query') ? 'is-invalid' : '' ?>" 
                                          id="base_query" 
                                          name="base_query" 
                                          rows="15" 
                                          required><?= old('base_query', $report['base_query']) ?></textarea>
                                <?php if (validation_show_error('base_query')): ?>
                                    <div class="invalid-feedback">
                                        <?= validation_show_error('base_query') ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Parsed Components Display (Read-only) -->
                        <?php if (!empty($selected_columns) || !empty($where_conditions)): ?>
<!-- Parsed Components Display -->
<div class="card mt-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">
            <i class="fas fa-cogs"></i> Parsed Query Components
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- Selected Columns -->
            <div class="col-md-6 mb-3">
                <h6>Selected Columns:</h6>
                <ul class="list-group">
                    <?php foreach ($selected_columns as $column): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <strong><?= esc($column['alias']) ?></strong>
                                <small class="text-muted d-block"><?= esc($column['original']) ?></small>
                            </span>
                            <span class="badge bg-<?= $column['display'] ? 'success' : 'secondary' ?>">
                                <?= $column['display'] ? 'Visible' : 'Hidden' ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- WHERE Conditions -->
            <div class="col-md-6 mb-3">
                <h6>WHERE Conditions:</h6>
                <ul class="list-group">
                    <?php foreach ($where_conditions as $condition): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= esc($condition['column'] ?? 'Condition') ?></strong>
                                    <small class="text-muted d-block">
                                        Type: <?= esc($condition['type']) ?> | 
                                        Operator: <?= esc($condition['operator']) ?>
                                    </small>
                                    <small><?= esc($condition['condition']) ?></small>
                                </div>
                                <span class="badge bg-<?= $condition['editable'] ? 'info' : 'secondary' ?>">
                                    <?= $condition['editable'] ? 'Editable' : 'Fixed' ?>
                                </span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- GROUP BY -->
            <?php if (!empty($group_by)): ?>
                <div class="col-md-6 mb-3">
                    <h6>GROUP BY:</h6>
                    <ul class="list-group">
                        <?php foreach ($group_by as $group): ?>
                            <li class="list-group-item">
                                <?= esc($group['original'] ?? $group) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- ORDER BY -->
            <?php if (!empty($order_by)): ?>
                <div class="col-md-6 mb-3">
                    <h6>ORDER BY:</h6>
                    <ul class="list-group">
                        <?php foreach ($order_by as $order): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><?= esc($order['original'] ?? $order['column']) ?></span>
                                <span class="badge bg-<?= $order['direction'] == 'ASC' ? 'success' : 'warning' ?>">
                                    <?= $order['direction'] ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Filter Parameters -->
            <?php if (!empty($filter_parameters)): ?>
                <div class="col-12 mt-3">
                    <h6>Filter Parameters:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Column</th>
                                    <th>Type</th>
                                    <th>Operator</th>
                                    <th>Values/Default</th>
                                    <th>Editable</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filter_parameters as $param): ?>
                                    <tr>
                                        <td><?= esc($param['column']) ?></td>
                                        <td>
                                            <span class="badge bg-info"><?= esc($param['type']) ?></span>
                                        </td>
                                        <td><?= esc($param['operator']) ?></td>
                                        <td>
                                            <?php if ($param['type'] === 'IN'): ?>
                                                <?= implode(', ', array_map('esc', $param['values'])) ?>
                                            <?php elseif ($param['type'] === 'BETWEEN'): ?>
                                                <?= esc($param['from']) ?> - <?= esc($param['to']) ?>
                                            <?php elseif ($param['type'] === 'LIKE'): ?>
                                                <?= esc($param['value']) ?> (wildcard)
                                            <?php elseif ($param['type'] === 'COMPARISON'): ?>
                                                <?= esc($param['value']) ?>
                                            <?php else: ?>
                                                <?= esc($param['value'] ?? 'N/A') ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $param['editable'] ? 'success' : 'secondary' ?>">
                                                <?= $param['editable'] ? 'Yes' : 'No' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <a href="<?= site_url('reports') ?>" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back to List
                                    </a>
                                    <div>
                                        <a href="<?= site_url('reports/preview/' . $report['id']) ?>" 
                                           class="btn btn-info me-2">
                                            <i class="fas fa-eye"></i> Preview
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Update Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>