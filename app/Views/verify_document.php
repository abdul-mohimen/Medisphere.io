<section class="mb-4 text-center">
    <span class="eyebrow">Document security</span>
    <h1>Verification Center</h1>
    <p class="text-muted">Use the verification code to confirm that a prescription or clinical document was issued by MediSphere.</p>
</section>

<div class="glass-panel mb-4" data-aos="fade-up">
    <form class="row g-3 align-items-end">
        <input type="hidden" name="route" value="verify-document">
        <div class="col-md-4"><label class="form-label">Document Type</label><select name="type" class="form-select"><option value="">Auto-detect</option><option value="prescription" <?= ($documentType ?? '') === 'prescription' ? 'selected' : '' ?>>Prescription</option><option value="clinical_document" <?= ($documentType ?? '') === 'clinical_document' ? 'selected' : '' ?>>Clinical Document</option></select></div>
        <div class="col-md-6"><label class="form-label">Verification Code</label><input type="text" name="code" class="form-control" value="<?= e($verificationCode ?? '') ?>" placeholder="RX-XXXXXXXXXXXX / DOC-XXXXXXXXXXXX"></div>
        <div class="col-md-2 d-grid"><button class="btn btn-primary">Verify</button></div>
    </form>
</div>

<?php if (!empty($verificationCode)): ?>
    <div class="glass-panel secured-document-sheet" data-aos="fade-up">
        <?php if (!empty($record)): ?>
            <div class="verification-ribbon <?= !empty($integrityValid) ? 'valid' : 'invalid' ?>">
                <?= !empty($integrityValid) ? 'Authentic Document Verified' : 'Document Found But Integrity Check Failed' ?>
            </div>
            <div class="row g-4">
                <div class="col-lg-8">
                    <h3 class="mb-2"><?= e($record['title'] ?? ($record['disease_name'] ?? 'Verified Document')) ?></h3>
                    <div class="small text-muted text-capitalize mb-3"><?= e(str_replace('_', ' ', $documentType ?: 'document')) ?></div>
                    <div class="verification-summary-grid">
                        <div><span>Doctor</span><strong>Dr. <?= e($record['doctor_name'] ?? 'Doctor') ?></strong></div>
                        <div><span>Patient</span><strong><?= e($record['patient_name'] ?? 'Patient') ?></strong></div>
                        <div><span>Issued</span><strong><?= e($record['created_at'] ?? $record['issue_date'] ?? '') ?></strong></div>
                        <div><span>Verification Code</span><strong><?= e($record['verification_code'] ?? '') ?></strong></div>
                        <div><span>Integrity Hash</span><strong><?= e(substr((string) ($record['integrity_hash'] ?? ''), 0, 24)) ?>...</strong></div>
                        <div><span>Status</span><strong class="text-capitalize"><?= e($record['status'] ?? 'finalized') ?></strong></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="signature-box h-100">
                        <div class="small text-muted">Signature Snapshot</div>
                        <?php if (!empty($record['signature_image_path'])): ?><img src="<?= app_url($record['signature_image_path']) ?>" alt="Signature" class="saved-signature-image mb-3"><?php endif; ?>
                        <div class="fw-semibold"><?= e($record['digital_signature'] ?? 'Doctor Signature') ?></div>
                        <div class="small text-muted mt-2">Issued by <?= e($record['signature_snapshot_name'] ?? ($record['doctor_name'] ?? 'Doctor')) ?></div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="verification-ribbon invalid">Verification Code Not Found</div>
            <p class="mb-0">No prescription or clinical document matched the supplied verification code.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>
