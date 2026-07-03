<?= $this->extend('template') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-plus-circle"></i> Create New Report
                    </h4>
                </div>
                <div class="card-body">
                    <form method="post" action="<?= site_url('reports/store') ?>">
                        <?= csrf_field() ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="report_name" class="form-label">Report Name *</label>
                                <input type="text" 
                                       class="form-control <?= validation_show_error('report_name') ? 'is-invalid' : '' ?>" 
                                       id="report_name" 
                                       name="report_name" 
                                       value="<?= old('report_name') ?>" 
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
                                          rows="2"><?= old('description') ?></textarea>
                                <div class="form-text">Brief description of what this report shows</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="base_query" class="form-label">SQL Query *</label>
                                <div class="mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="validateQuery()">
                                        <i class="fas fa-check"></i> Validate SQL
                                    </button>
                                    <small class="text-muted ms-2">Only SELECT queries are allowed</small>
                                </div>
                                <textarea class="form-control sql-editor <?= validation_show_error('base_query') ? 'is-invalid' : '' ?>" 
                                          id="base_query" 
                                          name="base_query" 
                                          rows="15" 
                                          required 
                                          placeholder="SELECT * FROM table WHERE conditions..."><?= old('base_query') ?></textarea>
                                <?php if (validation_show_error('base_query')): ?>
                                    <div class="invalid-feedback">
                                        <?= validation_show_error('base_query') ?>
                                    </div>
                                <?php endif; ?>
                                <div class="form-text">
                                    <strong>Example:</strong> SELECT column1, column2 FROM table WHERE condition GROUP BY column1 ORDER BY column2
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <a href="<?= site_url('reports') ?>" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Report
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Example Queries Card -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-code"></i> Example SQL Queries
                    </h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="exampleQueries">
                        <?php
                        $examples = [
                            'Basic Report' => "SELECT ha.candidate_id, CONCAT(UPPER(can.last_name),' ',can.first_name) as candidate_name, ofl.location as office_location FROM candidates can JOIN home_application ha on can.id=ha.candidate_id JOIN office_locations ofl on ofl.id=can.registration_office WHERE can.registration_office IN (112,114,115) ORDER BY can.last_name",
                            'Aggregate Report' => "SELECT jo.candidate_id, CONCAT(UPPER(can.last_name),' ',can.first_name) as candidate_name, ROUND(SUM(TIMESTAMPDIFF(MINUTE, ts.start_date_time, ts.end_date_time)) / 60, 2) AS total_hours FROM joborders jo JOIN timesheets ts on jo.id=ts.job_id JOIN candidates can on can.id=jo.candidate_id WHERE jo.agency_id='118289' GROUP BY can.id",
                            'Complex Report' => "SELECT cad.candidate_id, CONCAT(UPPER(can.last_name),' ',can.first_name) AS candidate_name, DATE(jo.start_date) AS first_joborder_date, ro.role_name, cl.client_name FROM candidates can JOIN candidates_additional cad ON can.id = cad.candidate_id JOIN joborders jo ON jo.candidate_id = can.id JOIN roles ro ON ro.id = jo.roles JOIN client cl ON cl.id = jo.client_id WHERE LOWER(cad.active_candidate) = 'yes' ORDER BY first_joborder_date ASC"
                        ];
                        
                        $i = 1;
                        foreach ($examples as $title => $query):
                        ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#example<?= $i ?>">
                                    <?= $title ?>
                                </button>
                            </h2>
                            <div id="example<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#exampleQueries">
                                <div class="accordion-body">
                                    <pre class="mb-0"><code class="sql"><?= htmlspecialchars($query) ?></code></pre>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="useExample('<?= addslashes($query) ?>')">
                                        <i class="fas fa-copy"></i> Use This Example
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php $i++; endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function useExample(query) {
    document.getElementById('base_query').value = query;
    // Trigger input event to resize textarea
    const event = new Event('input');
    document.getElementById('base_query').dispatchEvent(event);
}
</script>
<?= $this->endSection() ?>