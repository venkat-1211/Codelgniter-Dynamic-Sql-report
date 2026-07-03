<?= $this->extend('template') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-chart-bar text-primary"></i> Report Management
        </h1>
        <a href="<?= site_url('reports/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New Report
        </a>
    </div>

    <div class="row">
        <?php if (empty($reports)): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    No reports found. <a href="<?= site_url('reports/create') ?>">Create your first report</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($reports as $report): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card report-card h-100">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-alt text-primary"></i> 
                                <?= esc($report['report_name']) ?>
                            </h5>
                            <span class="badge bg-<?= $report['is_active'] ? 'success' : 'secondary' ?>">
                                <?= $report['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <p class="card-text text-muted">
                                <?= esc($report['description'] ?: 'No description') ?>
                            </p>
                            <div class="small text-muted mb-2">
                                <i class="far fa-calendar"></i> 
                                Created: <?= date('M d, Y', strtotime($report['created_at'])) ?>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="btn-group w-100" role="group">
                                <a href="<?= site_url('reports/preview/' . $report['id']) ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye"></i> Preview
                                </a>
                                <a href="<?= site_url('reports/edit/' . $report['id']) ?>" 
                                   class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="<?= site_url('reports/export/' . $report['id'] . '/csv') ?>" 
                                   class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-download"></i> Export
                                </a>
                                <form action="<?= site_url('reports/delete/' . $report['id']) ?>" 
                                      method="post" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" 
                                            class="btn btn-outline-danger btn-sm"
                                            onclick="return confirmDelete('<?= esc($report['report_name']) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>