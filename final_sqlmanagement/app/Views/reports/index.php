<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .report-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .complex-badge {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
        }
        .template-badge {
            background: linear-gradient(45deg, #1dd1a1, #10ac84);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-chart-line"></i> Dynamic Report System
            </a>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2><i class="fas fa-file-alt"></i> Reports</h2>
                <p class="text-muted">Manage and execute dynamic reports</p>
            </div>
            <div class="col-md-6 text-end">
                <a href="<?= base_url('reports/builder') ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create New Report
                </a>
                <!-- <a href="<?= base_url('reports/templates') ?>" class="btn btn-success">
                    <i class="fas fa-clone"></i> Use Template
                </a> -->
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search reports...">
                            <button class="btn btn-outline-secondary" type="button" id="searchButton">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="btn-group">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" data-filter="all">All Reports</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="template">Templates</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="complex">Complex Reports</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="reportsContainer">
                    <?php foreach ($data as $report): ?>
                    <div class="col-md-4 mb-4 report-item" data-complexity="<?= strlen($report['description']) > 100 ? 'complex' : 'simple' ?>">
                        <div class="card report-card h-100" onclick="window.location.href='<?= base_url('reports/execute/' . $report['id']) ?>'">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="card-title"><?= htmlspecialchars($report['report_name']) ?></h5>
                                    <div>
                                        <?php if ($report['is_template']): ?>
                                            <span class="badge template-badge">Template</span>
                                        <?php endif; ?>
                                        <?php if (strlen($report['description']) > 100): ?>
                                            <span class="badge complex-badge">Complex</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="card-text text-muted small"><?= htmlspecialchars(substr($report['description'], 0, 100)) ?>...</p>
                                <div class="mt-3">
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-table"></i> <?= htmlspecialchars($report['base_table']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($report['created_at'])) ?>
                                    </small>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('reports/execute/' . $report['id']) ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-play"></i> Run
                                        </a>
                                        <a href="<?= base_url('reports/builder/' . $report['id']) ?>" class="btn btn-outline-secondary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="<?= base_url('reports/clone/' . $report['id']) ?>" class="btn btn-outline-success">
                                            <i class="fas fa-copy"></i> Clone
                                        </a>
                                        <button class="btn btn-outline-danger" data-id="<?= $report['id'] ?>" onclick="delete_report(event, this)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (empty($data)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                    <h4>No reports found</h4>
                    <p class="text-muted">Create your first dynamic report to get started</p>
                    <a href="<?= base_url('reports/builder') ?>" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Report
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Report pagination">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page || $i == $page - 1 || $i == $page + 1 || $i == 1 || $i == $totalPages): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php elseif ($i == $page - 2 || $i == $page + 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <li class="page-item <?= $page == $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Search functionality
            $('#searchInput, #searchButton').on('input keyup', function() {
                var search = $('#searchInput').val().toLowerCase();
                $('.report-item').each(function() {
                    var text = $(this).text().toLowerCase();
                    $(this).toggle(text.indexOf(search) > -1);
                });
            });

            // Filter functionality
            $('[data-filter]').click(function(e) {
                e.preventDefault();
                var filter = $(this).data('filter');
                
                $('.report-item').each(function() {
                    if (filter === 'all') {
                        $(this).show();
                    } else if (filter === 'template') {
                        var isTemplate = $(this).find('.template-badge').length > 0;
                        $(this).toggle(isTemplate);
                    } else if (filter === 'complex') {
                        var isComplex = $(this).find('.complex-badge').length > 0;
                        $(this).toggle(isComplex);
                    }
                });
            });
        });

        function delete_report(event, el)
        {
            event.preventDefault();
            if (!confirm('Are you sure you want to delete this report?')) {
                return; // ❌ user cancelled
            }
            const id = $(el).data('id');
            console.log(id);
            $.ajax({
                url: '<?= base_url("reports/delete") ?>',
                type: 'POST',
                data: { id: id },
                success: function (response) {
                    alert(response.message);
                    // window.location.reload(); 
                },
                error: function (xhr) {
                    alert('Delete failed');
                    console.error(xhr.responseText);
                }
            });
        }
    </script>
</body>
</html>