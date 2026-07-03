<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?> - Report Results</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css" rel="stylesheet">
    <style>
        .parameter-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .sql-preview {
            background: #1e1e1e;
            color: #d4d4d4;
            font-family: 'Consolas', 'Monaco', monospace;
            padding: 15px;
            border-radius: 5px;
            max-height: 400px;
            overflow: auto;
            font-size: 12px;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .data-table {
            font-size: 14px;
        }
        .export-buttons {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?= site_url('/') ?>">Report System</a>
            <div class="navbar-nav">
                <a class="nav-link" href="<?= site_url('reports') ?>">Back to Reports</a>
                <a class="nav-link" href="<?= site_url('reports/edit/' . ($report['id'] ?? '')) ?>">Edit Report</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4"><?= esc($title) ?></h2>
        
        <!-- Flash Messages -->
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Parameters Card -->
        <?php if (!empty($definition['parameters']) || !empty($parameters)): ?>
            <div class="parameter-card">
                <h5>Report Parameters</h5>
                <form method="get" action="<?= current_url() ?>" class="row g-3">
                    <?php foreach ($definition['parameters'] ?? [] as $param): ?>
                        <div class="col-md-3">
                            <label class="form-label"><?= $param['parameter_label'] ?></label>
                            <?php if ($param['input_type'] === 'select'): ?>
                                <select class="form-select" name="<?= $param['parameter_key'] ?>">
                                    <option value="">Select...</option>
                                    <!-- Options would be loaded dynamically -->
                                </select>
                            <?php elseif ($param['input_type'] === 'multiselect'): ?>
                                <select class="form-select select2" name="<?= $param['parameter_key'] ?>[]" multiple>
                                    <!-- Options would be loaded dynamically -->
                                </select>
                            <?php elseif ($param['input_type'] === 'date'): ?>
                                <input type="date" class="form-control" name="<?= $param['parameter_key'] ?>" 
                                       value="<?= $parameters[$param['parameter_key']] ?? '' ?>">
                            <?php elseif ($param['input_type'] === 'datetime-local'): ?>
                                <input type="datetime-local" class="form-control" name="<?= $param['parameter_key'] ?>" 
                                       value="<?= $parameters[$param['parameter_key']] ?? '' ?>">
                            <?php elseif ($param['input_type'] === 'checkbox'): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="<?= $param['parameter_key'] ?>" value="1"
                                           <?= ($parameters[$param['parameter_key']] ?? 0) ? 'checked' : '' ?>>
                                    <label class="form-check-label"><?= $param['parameter_label'] ?></label>
                                </div>
                            <?php else: ?>
                                <input type="text" class="form-control" name="<?= $param['parameter_key'] ?>" 
                                       value="<?= $parameters[$param['parameter_key']] ?? '' ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="<?= current_url() ?>" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Total Records</h5>
                        <h2><?= number_format($total) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Current Page</h5>
                        <h2><?= $current_page ?> of <?= $total_pages ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Quick Actions</h5>
                        <div class="btn-group" role="group">
                            <a href="?export=csv" class="btn btn-outline-primary">
                                <i class="fas fa-file-csv"></i> Export CSV
                            </a>
                            <a href="?export=excel" class="btn btn-outline-success">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </a>
                            <?php if (!empty($sql)): ?>
                                <button class="btn btn-outline-info" onclick="toggleSqlPreview()">
                                    <i class="fas fa-code"></i> Show SQL
                                </button>
                            <?php endif; ?>
                            <a href="<?= site_url('reports/export/' . ($report['id'] ?? '')) ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-download"></i> Export Definition
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SQL Preview -->
        <?php if (!empty($sql)): ?>
            <div class="mb-4" id="sqlPreview" style="display: none;">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Generated SQL</h5>
                        <button class="btn btn-sm btn-outline-secondary" onclick="copySql()">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="sql-preview"><?= esc($sql) ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Data Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Report Data</h5>
                <div class="form-inline">
                    <select class="form-select form-select-sm" style="width: auto;" onchange="window.location.href = '?per_page=' + this.value">
                        <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $per_page == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $per_page == 100 ? 'selected' : '' ?>>100</option>
                        <option value="500" <?= $per_page == 500 ? 'selected' : '' ?>>500</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($data)): ?>
                    <div class="text-center py-5">
                        <h4 class="text-muted">No data found</h4>
                        <p class="text-muted">Try adjusting your parameters or filters.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover data-table">
                            <thead>
                                <tr>
                                    <?php foreach (array_keys($data[0]) as $column): ?>
                                        <th><?= $column ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $value): ?>
                                            <td><?= $value ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?= $current_page == 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=1<?= $this->buildQueryString(['page']) ?>">First</a>
                            </li>
                            <li class="page-item <?= $current_page == 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $current_page - 1 ?><?= $this->buildQueryString(['page']) ?>">Previous</a>
                            </li>
                            
                            <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                                <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= $this->buildQueryString(['page']) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= $current_page == $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $current_page + 1 ?><?= $this->buildQueryString(['page']) ?>">Next</a>
                            </li>
                            <li class="page-item <?= $current_page == $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $total_pages ?><?= $this->buildQueryString(['page']) ?>">Last</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Report Info -->
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title">Report Information</h5>
                <dl class="row">
                    <dt class="col-sm-3">Base Table</dt>
                    <dd class="col-sm-9"><?= $report['base_table'] ?? 'N/A' ?></dd>
                    
                    <dt class="col-sm-3">Description</dt>
                    <dd class="col-sm-9"><?= $report['description'] ?? 'No description' ?></dd>
                    
                    <dt class="col-sm-3">Created</dt>
                    <dd class="col-sm-9"><?= $report['created_at'] ?? 'N/A' ?></dd>
                    
                    <?php if (!empty($definition['columns'])): ?>
                        <dt class="col-sm-3">Columns</dt>
                        <dd class="col-sm-9">
                            <?= count($definition['columns']) ?> columns defined
                        </dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>

    <!-- Export Buttons -->
    <div class="export-buttons">
        <div class="btn-group-vertical" role="group">
            <button type="button" class="btn btn-primary btn-lg rounded-circle shadow" 
                    data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-download"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="?export=csv"><i class="fas fa-file-csv"></i> CSV Export</a></li>
                <li><a class="dropdown-item" href="?export=excel"><i class="fas fa-file-excel"></i> Excel Export</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= site_url('api/reports/' . ($report['id'] ?? '') . '/export/json') ?>">
                    <i class="fas fa-code"></i> JSON API
                </a></li>
            </ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('.data-table').DataTable({
                pageLength: <?= $per_page ?>,
                ordering: true,
                searching: true,
                info: false,
                lengthChange: false,
                order: []
            });
            
            // Initialize Select2
            $('.select2').select2({
                width: '100%'
            });
        });
        
        function toggleSqlPreview() {
            const preview = document.getElementById('sqlPreview');
            preview.style.display = preview.style.display === 'none' ? 'block' : 'none';
        }
        
        function copySql() {
            const sql = document.querySelector('.sql-preview').textContent;
            navigator.clipboard.writeText(sql).then(() => {
                alert('SQL copied to clipboard!');
            });
        }
        
        // Helper to build query string
        function buildQueryString(excludeParams = []) {
            const params = new URLSearchParams(window.location.search);
            excludeParams.forEach(param => params.delete(param));
            return params.toString() ? '&' + params.toString() : '';
        }
    </script>
</body>
</html>