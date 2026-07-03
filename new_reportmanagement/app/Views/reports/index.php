<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?> - Report Management System</title>
<!-- Use different CDN providers -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css" rel="stylesheet">
    <style>
        .report-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .quick-actions {
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
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="<?= site_url('reports') ?>">Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('reports/create') ?>">Create</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('reports/import') ?>">Import</a>
                    </li>
                </ul>
                <form class="d-flex" action="<?= site_url('reports/search') ?>" method="get">
                    <input class="form-control me-2" type="search" name="q" placeholder="Search reports...">
                    <button class="btn btn-outline-light" type="submit">Search</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Flash Messages -->
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <h5>Total Reports</h5>
                    <h2><?= $stats['total_reports'] ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <h5>Active Reports</h5>
                    <h2><?= $stats['active_reports'] ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <h5>Templates</h5>
                    <h2><?= $stats['templates'] ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <h5>Tables Used</h5>
                    <h2><?= count($stats['by_table']) ?></h2>
                </div>
            </div>
        </div>

        <!-- Reports Grid -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Reports</h5>
                <a href="<?= site_url('reports/create') ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create New Report
                </a>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($reports as $report): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card report-card" onclick="window.location.href='<?= site_url('reports/view/' . $report['id']) ?>'">
                                <div class="card-body">
                                    <h5 class="card-title"><?= esc($report['report_name']) ?></h5>
                                    <p class="card-text text-muted">
                                        <small>Table: <?= esc($report['base_table']) ?></small>
                                    </p>
                                    <?php if ($report['description']): ?>
                                        <p class="card-text"><?= esc(substr($report['description'], 0, 100)) ?>...</p>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            Created: <?= date('M d, Y', strtotime($report['created_at'])) ?>
                                        </small>
                                        <span class="badge bg-<?= $report['is_active'] ? 'success' : 'danger' ?>">
                                            <?= $report['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?= site_url('reports/view/' . $report['id']) ?>" class="btn btn-outline-primary">
                                            Run
                                        </a>
                                        <a href="<?= site_url('reports/edit/' . $report['id']) ?>" class="btn btn-outline-secondary">
                                            Edit
                                        </a>
                                        <button class="btn btn-outline-danger" onclick="deleteReport(<?= $report['id'] ?>, '<?= esc($report['report_name']) ?>')">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $current_page == 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $current_page - 1 ?>">Previous</a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= $current_page == $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $current_page + 1 ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <?php if (!empty($stats['recent'])): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($stats['recent'] as $activity): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= esc($activity['report_name']) ?></h6>
                                    <small><?= strtotime($activity['created_at']), time(), 2 ?> ago</small>
                                </div>
                                <small>Created on <?= date('F j, Y g:i A', strtotime($activity['created_at'])) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <button class="btn btn-primary btn-lg rounded-circle shadow" data-bs-toggle="modal" data-bs-target="#quickCreateModal">
            <i class="fas fa-bolt"></i>
        </button>
    </div>

    <!-- Quick Create Modal -->
    <div class="modal fade" id="quickCreateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Quick Create Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="<?= site_url('reports/create') ?>" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Report Name</label>
                            <input type="text" class="form-control" name="report_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Base Table</label>
                            <select class="form-select select2" name="base_table" required>
                                <option value="">Select a table...</option>
                                <?php if (isset($tables) && !empty($tables)): ?>
                                    <?php foreach ($tables as $table): ?>
                                        <option value="<?= $table ?>"><?= $table ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete report "<span id="reportName"></span>"?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    
    <script>
        let reportToDelete = null;
        
        function deleteReport(id, name) {
            event.stopPropagation();
            reportToDelete = id;
            document.getElementById('reportName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (!reportToDelete) return;
            
            fetch(`<?= site_url('reports/delete') ?>/${reportToDelete}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to delete report: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        });
        
        // Initialize Select2
        $(document).ready(function() {

            $('.select2').select2({
                width: '100%'
            });
            
            // Initialize DataTables if any table exists
            $('table').DataTable();

            // Load tables when modal is shown
            $('#quickCreateModal').on('show.bs.modal', function() {
                const select = $(this).find('select[name="base_table"]');
                if (select.find('option').length <= 1) { // Only has "Select a table..." option
                    fetch('<?= site_url("reports/get-table-columns") ?>?action=tables')
                        .then(response => response.json())
                        .then(tables => {
                            tables.forEach(table => {
                                select.append(`<option value="${table}">${table}</option>`);
                            });
                        });
                }
            });
        });
    </script>
</body>
</html>