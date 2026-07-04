<section class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <span class="eyebrow">Patient privacy</span>
        <h1 class="mb-0">Record Consents</h1>
    </div>
    <div class="small text-muted">Grant or revoke hospital access to your medical records with an explicit scope and expiry date.</div>
</section>

<div class="ux-stepper" data-stepper>
    <div class="ux-stepper-nav" role="tablist" aria-label="Record consent sections">
        <button class="ux-stepper-tab is-active" data-stepper-tab data-stepper-target="consent-step-grant">
            <span class="ux-stepper-number">1</span>
            <span class="ux-stepper-label"><strong>Grant</strong><span>Share records</span></span>
        </button>
        <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="consent-step-active">
            <span class="ux-stepper-number">2</span>
            <span class="ux-stepper-label"><strong>Consents</strong><span>Active and past</span></span>
        </button>
        <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="consent-step-log">
            <span class="ux-stepper-number">3</span>
            <span class="ux-stepper-label"><strong>Access Log</strong><span>Audit history</span></span>
        </button>
    </div>
    <div class="ux-stepper-panels">
        <section class="ux-stepper-panel is-active" id="consent-step-grant" data-stepper-panel>
<div class="row g-4">
    <div class="col-12" data-aos="fade-up">
        <div class="glass-panel h-100">
            <h4 class="mb-3">Grant New Consent</h4>
            <form method="POST" action="<?= route_url('record-consents/save') ?>" class="row g-3">
                <?= csrf_field() ?>
                <div class="col-12"><label class="form-label">Hospital</label><select name="hospital_id" class="form-select" required><option value="">Select hospital</option><?php foreach (($hospitals ?? []) as $hospital): ?><option value="<?= (int) $hospital['id'] ?>"><?= e($hospital['name']) ?> — <?= e($hospital['city']) ?>, <?= e($hospital['country']) ?></option><?php endforeach; ?></select></div>
                <div class="col-12">
                    <label class="form-label d-block">Record Scope</label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach (['profile' => 'Profile', 'reports' => 'Reports', 'prescriptions' => 'Prescriptions', 'documents' => 'Clinical Documents', 'appointments' => 'Appointments', 'scans' => 'AI Scans'] as $value => $label): ?>
                            <label class="chip-check"><input type="checkbox" name="scope[]" value="<?= e($value) ?>"> <span><?= e($label) ?></span></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-6"><label class="form-label">Start Date</label><input type="date" name="starts_at" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                <div class="col-md-6"><label class="form-label">Expires At</label><input type="date" name="expires_at" class="form-control"></div>
                <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="4" placeholder="Purpose of access, doctor instruction, temporary treatment window"></textarea></div>
                <div class="col-12 d-grid"><button class="btn btn-primary">Grant Consent</button></div>
            </form>
        </div>
    </div>
    </div>
        </section>
        <section class="ux-stepper-panel" id="consent-step-active" data-stepper-panel>
<div class="row g-4">

    <div class="col-12" data-aos="fade-up">
        <div class="glass-panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">My Active & Past Consents</h4>
                <span class="small text-muted">Hospitals can only access records while consent is active.</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Hospital</th><th>Scope</th><th>Status</th><th>Valid Through</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach (($consents ?? []) as $consent): ?>
                        <tr>
                            <td><?= e($consent['hospital_name']) ?><div class="small text-muted"><?= e($consent['city']) ?>, <?= e($consent['country']) ?></div></td>
                            <td><?= e($consent['scope']) ?></td>
                            <td><span class="badge text-bg-light text-capitalize"><?= e($consent['status']) ?></span></td>
                            <td><?= e($consent['expires_at'] ?: 'No expiry') ?></td>
                            <td>
                                <?php if (($consent['status'] ?? '') === 'active'): ?>
                                    <form method="POST" action="<?= route_url('record-consents/revoke') ?>" onsubmit="return confirm('Revoke this hospital consent?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="consent_id" value="<?= (int) $consent['id'] ?>">
                                        <button class="btn btn-outline-danger btn-sm">Revoke</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted small">Inactive</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($consents)): ?><tr><td colspan="5" class="text-center text-muted py-4">No record consents granted yet. Select a hospital, define the scope, and set an expiry date to share records securely.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

        </section>
        <section class="ux-stepper-panel" id="consent-step-log" data-stepper-panel>
<div class="glass-panel mt-4" data-aos="fade-up">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Access Log</h4>
        <span class="small text-muted">See when hospital staff accessed your records</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Hospital</th><th>Accessor</th><th>Access Type</th><th>When</th></tr></thead>
            <tbody>
            <?php foreach (($accessLogs ?? []) as $log): ?>
                <tr>
                    <td><?= e($log['hospital_name']) ?></td>
                    <td><?= e($log['accessor_email']) ?></td>
                    <td><?= e($log['access_type']) ?></td>
                    <td><?= e($log['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($accessLogs)): ?><tr><td colspan="4" class="text-center text-muted py-4">No hospital has accessed your records yet. Consent-based access activity will be logged here for transparency.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
        </section>
    </div>
</div>
