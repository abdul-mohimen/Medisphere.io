<section class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <span class="eyebrow">Privacy & compliance</span>
        <h1 class="mb-0">Compliance Center</h1>
    </div>
    <div class="small text-muted">Acknowledge policies and submit privacy/data rights requests.</div>
</section>

<div class="ux-stepper" data-stepper>
    <div class="ux-stepper-nav" role="tablist" aria-label="Compliance center sections">
        <button class="ux-stepper-tab is-active" data-stepper-tab data-stepper-target="user-compliance-step-policies">
            <span class="ux-stepper-number">1</span>
            <span class="ux-stepper-label"><strong>Policies</strong><span>Read and acknowledge</span></span>
        </button>
        <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="user-compliance-step-requests">
            <span class="ux-stepper-number">2</span>
            <span class="ux-stepper-label"><strong>Requests</strong><span>Privacy rights</span></span>
        </button>
    </div>
    <div class="ux-stepper-panels">
        <section class="ux-stepper-panel is-active" id="user-compliance-step-policies" data-stepper-panel>
<div class="row g-4">
    <div class="col-12" data-aos="fade-up">
        <div class="glass-panel h-100">
            <h4 class="mb-3">Pending Policy Acknowledgements</h4>
            <div class="compliance-list-grid">
                <?php foreach (($pendingPolicies ?? []) as $policy): ?>
                    <div class="compliance-card">
                        <div>
                            <strong><?= e($policy['title']) ?></strong>
                            <div class="small text-muted text-capitalize"><?= e(str_replace('_', ' ', $policy['category'])) ?> · <?= e($policy['version_label']) ?></div>
                            <div class="small text-muted">Effective <?= e($policy['effective_date']) ?></div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap mt-3">
                            <a href="<?= route_url('policy', ['slug' => $policy['slug']]) ?>" class="btn btn-outline-primary btn-sm" target="_blank">Read Policy</a>
                            <form method="POST" action="<?= route_url('compliance/acknowledge') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="policy_id" value="<?= (int) $policy['id'] ?>">
                                <button class="btn btn-primary btn-sm">Acknowledge</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($pendingPolicies)): ?><div class="text-muted">No pending acknowledgements. New policy updates requiring your review will appear here.</div><?php endif; ?>
            </div>

            <hr>
            <h4 class="mb-3">Published Policies</h4>
            <div class="compliance-list-grid">
                <?php foreach (($publicPolicies ?? []) as $policy): ?>
                    <a class="compliance-card text-decoration-none" href="<?= route_url('policy', ['slug' => $policy['slug']]) ?>">
                        <strong><?= e($policy['title']) ?></strong>
                        <div class="small text-muted text-capitalize"><?= e(str_replace('_', ' ', $policy['category'])) ?> · <?= e($policy['version_label']) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    </div>
        </section>
        <section class="ux-stepper-panel" id="user-compliance-step-requests" data-stepper-panel>
<div class="row g-4">

    <div class="col-12" data-aos="fade-up">
        <div class="glass-panel h-100">
            <h4 class="mb-3">Submit Data Rights Request</h4>
            <form method="POST" action="<?= route_url('compliance/request') ?>" class="row g-3 mb-4">
                <?= csrf_field() ?>
                <div class="col-md-4"><label class="form-label">Request Type</label><select name="request_type" class="form-select"><option value="access">Access</option><option value="export">Export</option><option value="delete">Delete</option><option value="correction">Correction</option><option value="restriction">Restriction</option></select></div>
                <div class="col-md-8"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3" placeholder="Describe the data rights request and the records involved" required></textarea></div>
                <div class="col-12 d-grid"><button class="btn btn-primary">Submit Request</button></div>
            </form>

            <h4 class="mb-3">My Privacy Requests</h4>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Type</th><th>Status</th><th>Submitted</th><th>Admin Notes</th></tr></thead>
                    <tbody>
                    <?php foreach (($requests ?? []) as $request): ?>
                        <tr>
                            <td class="text-capitalize"><?= e($request['request_type']) ?></td>
                            <td><span class="badge text-bg-light text-capitalize"><?= e($request['status']) ?></span></td>
                            <td><?= e($request['created_at']) ?></td>
                            <td><?= e($request['admin_notes'] ?: 'Pending review') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($requests)): ?><tr><td colspan="4" class="text-center text-muted py-4">No privacy requests submitted yet. Use the form above to request access, export, correction, deletion, or processing restriction.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <hr>
            <h4 class="mb-3">Acknowledgement History</h4>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Policy</th><th>Category</th><th>Version</th><th>Acknowledged</th></tr></thead>
                    <tbody>
                    <?php foreach (($acknowledgements ?? []) as $ack): ?>
                        <tr>
                            <td><?= e($ack['title']) ?></td>
                            <td class="text-capitalize"><?= e(str_replace('_', ' ', $ack['category'])) ?></td>
                            <td><?= e($ack['version_label']) ?></td>
                            <td><?= e($ack['acknowledged_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($acknowledgements)): ?><tr><td colspan="4" class="text-center text-muted py-4">No policy acknowledgements recorded yet. Completed acknowledgements will appear here with version and timestamp details.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
        </section>
    </div>
</div>
