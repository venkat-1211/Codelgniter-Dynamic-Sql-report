<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Results: <?= htmlspecialchars($report['report_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .result-container {
            min-height: calc(100vh - 200px);
        }
        .data-table th {
            background-color: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .sql-preview {
            max-height: 200px;
            overflow-y: auto;
            font-size: 0.85em;
        }
        .parameter-badge {
            font-size: 0.8em;
        }
        .export-buttons .btn {
            min-width: 120px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= base_url('reports') ?>">
                <i class="fas fa-arrow-left"></i> Report Results
            </a>
        </div>
    </nav>

    <div class="container-fluid mt-4 result-container">
        <!-- Report Header -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><?= htmlspecialchars($report['report_name']) ?></h4>
                        <small><?= htmlspecialchars($report['description']) ?></small>
                    </div>
                    <div class="export-buttons">
                        <a href="<?= base_url('reports/export-csv/' . $report['id']) . '?' . http_build_query($parameters) ?>" 
                           class="btn btn-light btn-sm">
                            <i class="fas fa-file-csv"></i> CSV
                        </a>
                        <a href="<?= base_url('reports/export-excel/' . $report['id']) . '?' . http_build_query($parameters) ?>" 
                           class="btn btn-light btn-sm">
                            <i class="fas fa-file-excel"></i> Excel
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Parameters -->
                <?php if (!empty($parameters)): ?>
                <div class="mb-3">
                    <h6><i class="fas fa-sliders-h"></i> Parameters Applied:</h6>
                    <div>
                        <?php foreach ($parameters as $key => $value): ?>
                            <?php if (!empty($value)): ?>
                                <span class="badge bg-info parameter-badge me-2 mb-2">
                                    <?= htmlspecialchars($key) ?>: <?= htmlspecialchars(is_array($value) ? implode(', ', $value) : $value) ?>
                                </span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="card-title"><?= number_format($total) ?></h5>
                                <p class="card-text text-muted">Total Records</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="card-title"><?= number_format(count($data)) ?></h5>
                                <p class="card-text text-muted">Current Page</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="card-title"><?= $page ?></h5>
                                <p class="card-text text-muted">Page Number</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="card-title"><?= $perPage ?></h5>
                                <p class="card-text text-muted">Per Page</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SQL Preview (Collapsible) -->
                <div class="accordion mb-3" id="sqlAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sqlCollapse">
                                <i class="fas fa-code me-2"></i> View Generated SQL
                            </button>
                        </h2>
                        <div id="sqlCollapse" class="accordion-collapse collapse" data-bs-parent="#sqlAccordion">
                            <div class="accordion-body">
                                <pre class="sql-preview bg-dark text-light p-3"><?= htmlspecialchars($sql) ?></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-table"></i> Results</h5>
                <div>
                    <form method="get" class="d-inline">
                        <?php foreach ($parameters as $key => $value): ?>
                            <?php if (!empty($value)): ?>
                                <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <div class="input-group input-group-sm" style="width: 200px;">
                            <select class="form-select" name="per_page" onchange="this.form.submit()">
                                <option value="10" <?= $perPage == 10 ? 'selected' : '' ?>>10 per page</option>
                                <option value="25" <?= $perPage == 25 ? 'selected' : '' ?>>25 per page</option>
                                <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50 per page</option>
                                <option value="100" <?= $perPage == 100 ? 'selected' : '' ?>>100 per page</option>
                                <option value="500" <?= $perPage == 500 ? 'selected' : '' ?>>500 per page</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($data)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover data-table">
                            <thead>
                                <tr>
                                    <?php foreach (array_keys($data[0]) as $column): ?>
                                        <th><?= htmlspecialchars($column) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $value): ?>
                                            <td><?= htmlspecialchars($value) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Results pagination">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($parameters, ['page' => $page - 1, 'per_page' => $perPage])) ?>">
                                    Previous
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i == $page || $i == $page - 1 || $i == $page + 1 || $i == 1 || $i == $totalPages): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($parameters, ['page' => $i, 'per_page' => $perPage])) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php elseif ($i == $page - 2 || $i == $page + 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= $page == $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($parameters, ['page' => $page + 1, 'per_page' => $perPage])) ?>">
                                    Next
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-database fa-4x text-muted mb-3"></i>
                        <h4>No results found</h4>
                        <p class="text-muted">Try adjusting your parameters or check the report definition</p>
                        <a href="<?= base_url('reports/builder/' . $report['id']) ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Report
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-4 text-center">
            <a href="<?= base_url('reports/execute/' . $report['id']) ?>" class="btn btn-outline-primary">
                <i class="fas fa-redo"></i> Re-run Report
            </a>
            <a href="<?= base_url('reports/builder/' . $report['id']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-edit"></i> Edit Report
            </a>
            <a href="<?= base_url('reports') ?>" class="btn btn-outline-dark">
                <i class="fas fa-list"></i> Back to Reports
            </a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('.data-table').DataTable({
                pageLength: <?= $perPage ?>,
                lengthChange: false,
                searching: true,
                ordering: true,
                info: true,
                autoWidth: false,
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
            });
        });
    </script>
</body>
</html>