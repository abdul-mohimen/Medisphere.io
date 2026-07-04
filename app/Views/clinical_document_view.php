<section class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <span class="eyebrow">Clinical document</span>
        <h1 class="mb-0"><?= e($document['title'] ?? 'Clinical Document') ?></h1>
        <p class="text-muted mb-0 mt-2">Review the signed clinical summary, patient context, verification code, and printable document record.</p>
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
                <span class="logo-badge"><i class="fa-solid fa-file-signature"></i></span>
                <span><?= e(ucwords(str_replace('_', ' ', $document['document_type'] ?? 'Clinical Document'))) ?></span>
            </div>
            <div class="small text-muted">Printable clinical document with secure doctor signature</div>
        </div>
        <div class="col-md-5 text-md-end">
            <div><strong>Status:</strong> <span class="badge text-bg-light text-capitalize"><?= e($document['status'] ?? 'finalized') ?></span></div>
            <div><strong>Issue Date:</strong> <?= e($document['issue_date'] ?? '') ?></div>
            <div><strong>Generated:</strong> <?= e($document['created_at'] ?? '') ?></div>
            <div><strong>Verification Code:</strong> <code><?= e($document['verification_code'] ?? '') ?></code></div>
        </div>
    </div>

    <hr>

    <div class="row g-4">
        <div class="col-md-6">
            <h5>Doctor</h5>
            <div class="invoice-meta">
                <div><strong>Name:</strong> Dr. <?= e($document['doctor_name'] ?? 'Doctor') ?></div>
                <div><strong>Specialization:</strong> <?= e($document['specialization'] ?? 'General') ?></div>
                <div><strong>License:</strong> <?= e($document['license_number'] ?? 'Pending') ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <h5>Patient</h5>
            <div class="invoice-meta">
                <div><strong>Name:</strong> <?= e($document['patient_name'] ?? 'Patient') ?></div>
                <div><strong>Email:</strong> <?= e($document['patient_email'] ?? '') ?></div>
                <div><strong>Appointment:</strong> <?= e($document['appointment_date'] ?? '') ?> <?= e($document['appointment_time'] ?? '') ?></div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <h5>Summary</h5>
        <p class="mb-0"><?= e($document['summary'] ?: 'No summary provided.') ?></p>
    </div>

    <div class="mt-4 clinical-document-content">
        <h5>Document Content</h5>
        <div class="document-body-panel"><?= nl2br(e($document['content'] ?: 'No content is available for this document yet. Finalized clinical notes will appear here.')) ?></div>
    </div>

    <div class="signature-box mt-4">
        <div class="small text-muted">Digital Signature</div>
        <?php if (!empty($document['signature_image_path'])): ?><img src="<?= app_url($document['signature_image_path']) ?>" alt="Signature" class="saved-signature-image mb-2"><?php endif; ?>
        <div class="fw-semibold"><?= e($document['digital_signature'] ?? 'Doctor Signature') ?></div>
        <div class="small text-muted mt-2">Signature snapshot: <?= e($document['signature_snapshot_name'] ?? 'N/A') ?></div>
        <div class="small text-muted">Integrity hash: <?= e(substr((string) ($document['integrity_hash'] ?? ''), 0, 20)) ?>...</div>
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
