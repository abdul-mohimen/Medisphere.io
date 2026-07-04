<?php $scopeSet = array_flip($scopes ?? []); ?>
<section class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <span class="eyebrow">Consent-controlled access</span>
        <h1 class="mb-0">Patient Record Summary</h1>
        <p class="text-muted mb-0 mt-2">View only the record categories explicitly shared by the patient for coordinated hospital care.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <span class="badge text-bg-light">Consent Scope: <?= e($consent['scope'] ?? '') ?></span>
        <a href="<?= route_url('hospital-management') ?>" class="btn btn-outline-primary btn-sm">Back to Hospital Management</a>
    </div>
</section>

<div class="row g-4">
    <div class="col-lg-4" data-aos="fade-up">
        <div class="glass-panel h-100">
            <div class="avatar-2xl mx-auto mb-3"><?= strtoupper(substr(($patient['name'] ?? 'P'), 0, 1)) ?></div>
            <h3 class="text-center"><?= e($patient['name'] ?? 'Patient') ?></h3>
            <div class="small text-muted text-center mb-3">Consent valid until <?= e($consent['expires_at'] ?: 'no expiry') ?></div>
            <div class="metric-list mt-3">
                <?php if (isset($scopeSet['profile'])): ?>
                    <div><span>Phone</span><strong><?= e($patient['phone'] ?? 'Not available') ?></strong></div>
                    <div><span>Gender</span><strong><?= e($patient['gender'] ?? 'Not available') ?></strong></div>
                    <div><span>Blood Group</span><strong><?= e($patient['blood_group'] ?? 'Not available') ?></strong></div>
                    <div><span>City</span><strong><?= e($patient['city'] ?? 'Not available') ?></strong></div>
                    <div><span>Country</span><strong><?= e($patient['country'] ?? 'Not available') ?></strong></div>
                <?php endif; ?>
                <div><span>Consent Notes</span><strong><?= e($consent['notes'] ?: 'No special notes') ?></strong></div>
            </div>
        </div>
    </div>

    <div class="col-lg-8" data-aos="fade-up">
        <div class="row g-4">
            <?php if (isset($scopeSet['reports'])): ?>
                <div class="col-12"><div class="glass-panel h-100"><h4 class="mb-3">Medical Reports</h4><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Type</th><th>File</th><th>Uploaded</th></tr></thead><tbody><?php foreach (($reports ?? []) as $report): ?><tr><td><?= e($report['report_type']) ?></td><td><?= e($report['original_name']) ?></td><td><?= e($report['uploaded_date']) ?></td></tr><?php endforeach; ?><?php if (empty($reports)): ?><tr><td colspan="3" class="text-center text-muted py-4">No reports are available in the shared scope yet. Uploaded lab, imaging, and prescription files will appear here.</td></tr><?php endif; ?></tbody></table></div></div></div>
            <?php endif; ?>
            <?php if (isset($scopeSet['prescriptions'])): ?>
                <div class="col-12"><div class="glass-panel h-100"><h4 class="mb-3">Prescriptions</h4><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Title</th><th>Doctor</th><th>Issued</th></tr></thead><tbody><?php foreach (($prescriptions ?? []) as $prescription): ?><tr><td><?= e($prescription['title']) ?><div class="small text-muted">Code <?= e($prescription['verification_code'] ?? 'N/A') ?></div></td><td><?= e($prescription['doctor_name']) ?></td><td><?= e($prescription['created_at']) ?></td></tr><?php endforeach; ?><?php if (empty($prescriptions)): ?><tr><td colspan="3" class="text-center text-muted py-4">No prescriptions are available in the shared scope yet. Signed medication records will appear here when issued.</td></tr><?php endif; ?></tbody></table></div></div></div>
            <?php endif; ?>
            <?php if (isset($scopeSet['documents'])): ?>
                <div class="col-12"><div class="glass-panel h-100"><h4 class="mb-3">Clinical Documents</h4><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Title</th><th>Type</th><th>Issue Date</th></tr></thead><tbody><?php foreach (($documents ?? []) as $document): ?><tr><td><?= e($document['title']) ?><div class="small text-muted">Code <?= e($document['verification_code'] ?? 'N/A') ?></div></td><td><?= e(str_replace('_', ' ', $document['document_type'])) ?></td><td><?= e($document['issue_date']) ?></td></tr><?php endforeach; ?><?php if (empty($documents)): ?><tr><td colspan="3" class="text-center text-muted py-4">No clinical documents are available in the shared scope yet. Certificates, treatment plans, and summaries will appear here.</td></tr><?php endif; ?></tbody></table></div></div></div>
            <?php endif; ?>
            <?php if (isset($scopeSet['appointments'])): ?>
                <div class="col-12"><div class="glass-panel h-100"><h4 class="mb-3">Appointments</h4><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Doctor</th><th>Date</th><th>Status</th></tr></thead><tbody><?php foreach (($appointments ?? []) as $appointment): ?><tr><td><?= e($appointment['doctor_name']) ?></td><td><?= e($appointment['date']) ?> <?= e($appointment['time']) ?></td><td><span class="badge text-bg-light text-capitalize"><?= e($appointment['status']) ?></span></td></tr><?php endforeach; ?><?php if (empty($appointments)): ?><tr><td colspan="3" class="text-center text-muted py-4">No appointments are available in the shared scope yet. Visit history will appear here when appointments are booked.</td></tr><?php endif; ?></tbody></table></div></div></div>
            <?php endif; ?>
            <?php if (isset($scopeSet['scans'])): ?>
                <div class="col-12"><div class="glass-panel h-100"><h4 class="mb-3">AI Disease Scans</h4><div class="row g-3"><?php foreach (($scans ?? []) as $scan): ?><div class="col-md-6"><div class="white-card h-100"><div class="small text-muted"><?= e($scan['timestamp']) ?></div><h6><?= e($scan['ai_result']) ?></h6><div class="small">Confidence <?= e((string) $scan['confidence_score']) ?>%</div></div></div><?php endforeach; ?><?php if (empty($scans)): ?><div class="col-12 text-muted">No scans are available in the shared scope yet. Saved AI scan summaries will appear here when the patient shares them.</div><?php endif; ?></div></div></div>
            <?php endif; ?>
        </div>
    </div>
</div>
