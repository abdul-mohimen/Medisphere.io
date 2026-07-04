<?php
$role = (string) ($role ?? '');
$reports = array_values((array) ($reports ?? []));
$reportTypes = array_values((array) ($reportTypes ?? ['Lab Tests', 'Prescriptions', 'X-Rays', 'MRI', 'CT Scan', 'Discharge Summary', 'General']));
$clinicalStats = is_array($clinicalStats ?? null) ? $clinicalStats : ['prescriptions' => 0, 'documents' => 0];
$clinicalPrescriptions = array_slice(array_values((array) ($clinicalPrescriptions ?? [])), 0, 3);
$clinicalDocuments = array_slice(array_values((array) ($clinicalDocuments ?? [])), 0, 3);
$maxUploadMb = (int) ($maxUploadMb ?? 10);
$uploadedThisMonth = count(array_filter($reports, static function (array $report): bool {
    return !empty($report['uploaded_date']) && date('Y-m', strtotime((string) $report['uploaded_date'])) === date('Y-m');
}));
$latestReport = $reports[0] ?? null;
$uniqueTypes = count(array_unique(array_map(static fn(array $report): string => (string) ($report['report_type'] ?? 'General'), $reports)));
?>
<section class="reports-page-hero glass-panel mb-4" data-aos="fade-up">
    <div class="reports-hero-copy">
        <span class="eyebrow">Reports and documents</span>
        <h1>Medical Reports</h1>
        <p>Upload lab results, imaging files, prescriptions, discharge summaries, and clinical notes so every appointment starts with complete context.</p>
        <?php if ($role === 'patient'): ?>
            <a href="#reports-step-upload" class="btn btn-primary btn-lg" data-report-jump-upload><i class="fa-solid fa-cloud-arrow-up me-2"></i> Upload Report</a>
        <?php else: ?>
            <a href="<?= route_url('clinical-records') ?>" class="btn btn-primary btn-lg"><i class="fa-solid fa-file-prescription me-2"></i> Open Clinical Records</a>
        <?php endif; ?>
    </div>
    <div class="reports-hero-panel" aria-label="Reports workflow">
        <span class="reports-hero-pill"><i class="fa-solid fa-shield-heart"></i> Care Record Flow</span>
        <div class="reports-flow-list">
            <div><strong>1</strong><span>Patient uploads private report files.</span></div>
            <div><strong>2</strong><span>Doctors issue signed prescriptions and documents in Clinical Records.</span></div>
            <div><strong>3</strong><span>Hospitals can view shared scope only after patient consent.</span></div>
        </div>
    </div>
</section>

<section class="reports-stat-grid mb-4" aria-label="Report summary">
    <div class="reports-stat-card"><span>Uploaded reports</span><strong><?= count($reports) ?></strong><small>Patient-owned files</small></div>
    <div class="reports-stat-card"><span>This month</span><strong><?= $uploadedThisMonth ?></strong><small>Recent additions</small></div>
    <div class="reports-stat-card"><span>Categories</span><strong><?= $uniqueTypes ?></strong><small>Lab, imaging, notes</small></div>
    <div class="reports-stat-card"><span>Clinical docs</span><strong><?= (int) ($clinicalStats['prescriptions'] ?? 0) + (int) ($clinicalStats['documents'] ?? 0) ?></strong><small>Doctor-issued records</small></div>
</section>

<?php if ($role !== 'patient'): ?>
    <section class="reports-guidance-card glass-panel" data-aos="fade-up">
        <div>
            <span class="eyebrow">Role guidance</span>
            <h3>Reports are uploaded by patients.</h3>
            <p>Doctors create verifiable prescriptions and clinical documents from Clinical Records. Hospitals review reports only through patient consent from the hospital patient record view.</p>
        </div>
        <div class="reports-guidance-actions">
            <a href="<?= route_url('clinical-records') ?>" class="btn btn-primary"><i class="fa-solid fa-file-signature me-2"></i> Clinical Records</a>
            <a href="<?= route_url('record-consents') ?>" class="btn btn-outline-primary"><i class="fa-solid fa-user-shield me-2"></i> Record Consents</a>
        </div>
    </section>
<?php else: ?>
    <div class="ux-stepper reports-stepper" data-stepper>
        <div class="ux-stepper-nav" role="tablist" aria-label="Medical report sections">
            <button class="ux-stepper-tab is-active" data-stepper-tab data-stepper-target="reports-step-upload">
                <span class="ux-stepper-number">1</span>
                <span class="ux-stepper-label"><strong>Upload</strong><span>Add a new file</span></span>
            </button>
            <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="reports-step-library">
                <span class="ux-stepper-number">2</span>
                <span class="ux-stepper-label"><strong>Library</strong><span>Search and manage</span></span>
            </button>
            <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="reports-step-context">
                <span class="ux-stepper-number">3</span>
                <span class="ux-stepper-label"><strong>Context</strong><span>Clinical documents</span></span>
            </button>
        </div>
        <div class="ux-stepper-panels">
            <section class="ux-stepper-panel is-active" id="reports-step-upload" data-stepper-panel>
                <div class="row g-4">
                    <div class="col-xl-7" data-aos="fade-up">
                        <div class="glass-panel h-100 report-upload-panel">
                            <div class="reports-panel-head">
                                <div>
                                    <span class="eyebrow">Secure upload</span>
                                    <h4>Upload new report</h4>
                                </div>
                                <span class="reports-limit-pill"><?= $maxUploadMb ?> MB max</span>
                            </div>
                            <form method="POST" action="<?= route_url('reports/upload') ?>" enctype="multipart/form-data" class="row g-3 mt-1">
                                <?= csrf_field() ?>
                                <div class="col-md-6">
                                    <label class="form-label">Category</label>
                                    <select name="report_type" class="form-select">
                                        <?php foreach ($reportTypes as $type): ?><option value="<?= e($type) ?>"><?= e($type) ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Report file</label>
                                    <input type="file" name="report_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                                </div>
                                <div class="col-12">
                                    <div class="reports-upload-note">
                                        <i class="fa-solid fa-circle-info"></i>
                                        <span>Accepted formats: PDF, JPG, PNG, and WebP. Use clear filenames so doctors can quickly understand the record during appointments.</span>
                                    </div>
                                </div>
                                <div class="col-12 d-grid"><button class="btn btn-primary btn-lg"><i class="fa-solid fa-cloud-arrow-up me-2"></i> Upload Report</button></div>
                            </form>
                        </div>
                    </div>
                    <div class="col-xl-5" data-aos="fade-up">
                        <div class="glass-panel h-100 reports-readiness-panel">
                            <span class="eyebrow">Record readiness</span>
                            <h4>What belongs here?</h4>
                            <div class="reports-readiness-list">
                                <div><i class="fa-solid fa-vial"></i><span>Lab results and trend reports.</span></div>
                                <div><i class="fa-solid fa-x-ray"></i><span>X-Ray, MRI, CT scan, and imaging summaries.</span></div>
                                <div><i class="fa-solid fa-file-medical"></i><span>Outside prescriptions, discharge notes, referrals, and care letters.</span></div>
                            </div>
                            <?php if ($latestReport): ?>
                                <div class="reports-latest-chip">
                                    <span>Latest upload</span>
                                    <strong><?= e($latestReport['original_name'] ?? 'Report') ?></strong>
                                    <small><?= e($latestReport['uploaded_date'] ?? '') ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="ux-stepper-panel" id="reports-step-library" data-stepper-panel>
                <div class="glass-panel reports-library-panel" data-aos="fade-up">
                    <div class="reports-panel-head">
                        <div>
                            <span class="eyebrow">Library</span>
                            <h4>Your reports</h4>
                        </div>
                        <input type="search" class="form-control reports-search" id="reportSearch" placeholder="Search by type or filename...">
                    </div>

                    <div class="reports-card-grid" id="reportsLibrary">
                        <?php foreach ($reports as $report): ?>
                            <?php
                            $fileUrl = app_url((string) $report['file_path']);
                            $extension = strtoupper(pathinfo((string) ($report['original_name'] ?? $report['file_path']), PATHINFO_EXTENSION) ?: 'FILE');
                            ?>
                            <article class="report-file-card" data-report-item>
                                <div class="report-file-icon"><i class="fa-solid <?= $extension === 'PDF' ? 'fa-file-pdf' : 'fa-file-image' ?>"></i></div>
                                <div class="report-file-main">
                                    <span><?= e($report['report_type'] ?? 'General') ?></span>
                                    <strong><?= e($report['original_name'] ?? 'Medical report') ?></strong>
                                    <small><?= e($extension) ?> file - Uploaded <?= e($report['uploaded_date'] ?? '') ?></small>
                                </div>
                                <div class="report-file-actions">
                                    <a href="<?= e($fileUrl) ?>" class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener"><i class="fa-solid fa-eye me-1"></i> Preview</a>
                                    <a href="<?= e($fileUrl) ?>" class="btn btn-outline-primary btn-sm" download><i class="fa-solid fa-download me-1"></i> Download</a>
                                    <form method="POST" action="<?= route_url('reports/delete') ?>" onsubmit="return confirm('Delete this report from your library?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
                                        <button class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-trash me-1"></i> Delete</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                        <?php if (!$reports): ?>
                            <div class="reports-empty-state">
                                <i class="fa-solid fa-folder-open"></i>
                                <strong>No reports uploaded yet.</strong>
                                <p>Add a lab result, imaging file, prescription, or clinical note so your care team has the full context.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="ux-stepper-panel" id="reports-step-context" data-stepper-panel>
                <div class="reports-clinical-context-note" data-aos="fade-up">
                    <i class="fa-solid fa-circle-info"></i>
                    <div>
                        <strong>Doctor-issued records are managed from Clinical Records.</strong>
                        <span>Reports are patient uploads, while prescriptions and certificates below are signed by doctors and can be opened or verified directly.</span>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-lg-6" data-aos="fade-up">
                        <div class="glass-panel h-100 reports-context-card">
                            <div class="reports-context-header">
                                <div>
                                    <i class="fa-solid fa-file-prescription"></i>
                                    <span>Doctor-issued prescriptions</span>
                                    <h4>Prescription Records</h4>
                                </div>
                                <strong class="reports-context-count"><?= (int) ($clinicalStats['prescriptions'] ?? 0) ?></strong>
                            </div>
                            <p>Medication plans signed by your doctor appear here with printable layouts, audit logs, and verification codes.</p>
                            <div class="reports-context-list">
                                <?php foreach ($clinicalPrescriptions as $prescription): ?>
                                    <article class="reports-context-item">
                                        <div>
                                            <strong><?= e($prescription['title'] ?? 'Prescription') ?></strong>
                                            <span>Dr. <?= e($prescription['doctor_name'] ?? 'Doctor') ?> - <?= e($prescription['created_at'] ?? '') ?></span>
                                            <code><?= e($prescription['verification_code'] ?? 'Pending') ?></code>
                                        </div>
                                        <div class="reports-context-actions">
                                            <a href="<?= route_url('clinical-records/prescription', ['id' => (int) $prescription['id']]) ?>" class="btn btn-outline-primary btn-sm">Open</a>
                                            <?php if (!empty($prescription['verification_code'])): ?>
                                                <a href="<?= route_url('verify-document', ['type' => 'prescription', 'code' => $prescription['verification_code']]) ?>" class="btn btn-primary btn-sm">Verify</a>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                                <?php if (!$clinicalPrescriptions): ?>
                                    <div class="reports-context-empty">
                                        <i class="fa-solid fa-file-circle-plus"></i>
                                        <span>No doctor-issued prescriptions yet. When a doctor creates one after an appointment, it will show here automatically.</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <a href="<?= route_url('clinical-records') ?>" class="btn btn-outline-primary">Open All Clinical Records</a>
                        </div>
                    </div>
                    <div class="col-lg-6" data-aos="fade-up">
                        <div class="glass-panel h-100 reports-context-card">
                            <div class="reports-context-header">
                                <div>
                                    <i class="fa-solid fa-file-shield"></i>
                                    <span>Verified certificates and summaries</span>
                                    <h4>Clinical Documents</h4>
                                </div>
                                <strong class="reports-context-count"><?= (int) ($clinicalStats['documents'] ?? 0) ?></strong>
                            </div>
                            <p>Medical certificates, discharge summaries, treatment plans, and follow-up notes are issued by doctors and secured with verification.</p>
                            <div class="reports-context-list">
                                <?php foreach ($clinicalDocuments as $document): ?>
                                    <article class="reports-context-item">
                                        <div>
                                            <strong><?= e($document['title'] ?? 'Clinical Document') ?></strong>
                                            <span><?= e(ucwords(str_replace('_', ' ', (string) ($document['document_type'] ?? 'document')))) ?> - <?= e($document['issue_date'] ?? '') ?></span>
                                            <code><?= e($document['verification_code'] ?? 'Pending') ?></code>
                                        </div>
                                        <div class="reports-context-actions">
                                            <a href="<?= route_url('clinical-records/document', ['id' => (int) $document['id']]) ?>" class="btn btn-outline-primary btn-sm">Open</a>
                                            <?php if (!empty($document['verification_code'])): ?>
                                                <a href="<?= route_url('verify-document', ['type' => 'clinical_document', 'code' => $document['verification_code']]) ?>" class="btn btn-primary btn-sm">Verify</a>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                                <?php if (!$clinicalDocuments): ?>
                                    <div class="reports-context-empty">
                                        <i class="fa-solid fa-file-circle-check"></i>
                                        <span>No certificates or summaries yet. Doctor-issued documents will be listed here as soon as they are created.</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <a href="<?= route_url('verify-document') ?>" class="btn btn-outline-primary">Verify by Code</a>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
<?php endif; ?>
