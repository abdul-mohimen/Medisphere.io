<section class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <span class="eyebrow">Prescription</span>
        <h1 class="mb-0"><?= e($prescription['title'] ?? 'Prescription') ?></h1>
        <p class="text-muted mb-0 mt-2">Review the signed medication plan, doctor details, verification code, and clinical advice.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= e($verifyUrl ?? '#') ?>" class="btn btn-outline-primary">Verify Authenticity</a>
        <button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print / Save PDF</button>
        <a href="<?= route_url('clinical-records') ?>" class="btn btn-outline-primary">Back to Records</a>
    </div>
</section>

<div class="glass-panel invoice-sheet secured-document-sheet" data-aos="fade-up">
    <div class="verification-ribbon <?= !empty($integrityValid) ? 'valid' : 'invalid' ?>">
        <?= !empty($integrityValid) ? 'Integrity Verified' : 'Integrity Check Failed' ?>
    </div>
    <div class="row g-4 align-items-start">
        <div class="col-md-7">
            <div class="brand-link mb-3">
                <span class="logo-badge"><i class="fa-solid fa-stethoscope"></i></span>
                <span>Doctor Prescription</span>
            </div>
            <div class="small text-muted">Digitally signed clinical prescription with verification code</div>
        </div>
        <div class="col-md-5 text-md-end">
            <div><strong>Status:</strong> <span class="badge text-bg-light text-capitalize"><?= e($prescription['status'] ?? 'finalized') ?></span></div>
            <div><strong>Issued:</strong> <?= e($prescription['created_at'] ?? '') ?></div>
            <div><strong>Follow-up:</strong> <?= e($prescription['follow_up_date'] ?: 'Not specified') ?></div>
            <div><strong>Verification Code:</strong> <code><?= e($prescription['verification_code'] ?? '') ?></code></div>
        </div>
    </div>

    <hr>

    <div class="row g-4">
        <div class="col-md-6">
            <h5>Doctor</h5>
            <div class="invoice-meta">
                <div><strong>Name:</strong> Dr. <?= e($prescription['doctor_name'] ?? 'Doctor') ?></div>
                <div><strong>Specialization:</strong> <?= e($prescription['specialization'] ?? 'General') ?></div>
                <div><strong>License:</strong> <?= e($prescription['license_number'] ?? 'Pending') ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <h5>Patient</h5>
            <div class="invoice-meta">
                <div><strong>Name:</strong> <?= e($prescription['patient_name'] ?? 'Patient') ?></div>
                <div><strong>Email:</strong> <?= e($prescription['patient_email'] ?? '') ?></div>
                <div><strong>Appointment:</strong> <?= e($prescription['appointment_date'] ?? '') ?> <?= e($prescription['appointment_time'] ?? '') ?></div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <h5>Diagnosis</h5>
        <p class="mb-0"><?= e($prescription['diagnosis'] ?: 'Not specified') ?></p>
    </div>

    <div class="table-responsive mt-4">
        <table class="table align-middle">
            <thead><tr><th>#</th><th>Medication Details</th></tr></thead>
            <tbody>
                <?php foreach ($medications as $index => $item): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= e($item) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$medications): ?><tr><td colspan="2" class="text-center text-muted">No medications are listed for this prescription. Any issued dosage instructions will appear in this table.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-md-6">
            <h5>Clinical Notes</h5>
            <p class="mb-0"><?= nl2br(e($prescription['notes'] ?: 'No additional notes.')) ?></p>
        </div>
        <div class="col-md-6">
            <h5>Advice</h5>
            <p class="mb-0"><?= nl2br(e($prescription['advice'] ?: 'No additional advice.')) ?></p>
        </div>
    </div>

    <div class="signature-box mt-4">
        <div class="small text-muted">Digital Signature</div>
        <?php if (!empty($prescription['signature_image_path'])): ?><img src="<?= app_url($prescription['signature_image_path']) ?>" alt="Signature" class="saved-signature-image mb-2"><?php endif; ?>
        <div class="fw-semibold"><?= e($prescription['digital_signature'] ?? 'Doctor Signature') ?></div>
        <div class="small text-muted mt-2">Signature snapshot: <?= e($prescription['signature_snapshot_name'] ?? 'N/A') ?></div>
        <div class="small text-muted">Integrity hash: <?= e(substr((string) ($prescription['integrity_hash'] ?? ''), 0, 20)) ?>...</div>
    </div>

    <div class="audit-trail-card mt-4">
        <h5>Audit Trail</h5>
        <div class="audit-log-list">
            <?php foreach (($auditLogs ?? []) as $log): ?>
                <div class="audit-log-item"><strong><?= e(ucfirst($log['action'])) ?></strong><span><?= e($log['created_at']) ?></span><div class="small text-muted"><?= e($log['actor_email'] ?? 'Public verification') ?></div></div>
            <?php endforeach; ?>
            <?php if (empty($auditLogs)): ?><div class="text-muted">No audit activity recorded yet. Verification and document access events will appear here as the record is reviewed.</div><?php endif; ?>
        </div>
    </div>
</div>
