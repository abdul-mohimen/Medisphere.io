<section class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <span class="eyebrow">Compliance operations</span>
        <h1 class="mb-0">Compliance Center</h1>
    </div>
    <div class="small text-muted">Manage policy documents, privacy requests, and compliance incidents.</div>
</section>

<div class="row g-4 mb-4">
    <div class="col-md-4"><div class="stat-card gradient-card"><span>Published Policies</span><h2><?= (int) ($stats['published_policies'] ?? 0) ?></h2></div></div>
    <div class="col-md-4"><div class="stat-card gradient-card"><span>Pending Data Requests</span><h2><?= (int) ($stats['pending_requests'] ?? 0) ?></h2></div></div>
    <div class="col-md-4"><div class="stat-card gradient-card"><span>Open Incidents</span><h2><?= (int) ($stats['open_incidents'] ?? 0) ?></h2></div></div>
</div>

<div class="ux-stepper" data-stepper>
    <div class="ux-stepper-nav" role="tablist" aria-label="Compliance operation sections">
        <button class="ux-stepper-tab is-active" data-stepper-tab data-stepper-target="compliance-step-policies">
            <span class="ux-stepper-number">1</span>
            <span class="ux-stepper-label"><strong>Policies</strong><span>Documents and acknowledgements</span></span>
        </button>
        <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="compliance-step-incidents">
            <span class="ux-stepper-number">2</span>
            <span class="ux-stepper-label"><strong>Incidents</strong><span>Security and privacy log</span></span>
        </button>
        <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="compliance-step-requests">
            <span class="ux-stepper-number">3</span>
            <span class="ux-stepper-label"><strong>Requests</strong><span>Data rights queue</span></span>
        </button>
    </div>
    <div class="ux-stepper-panels">
        <section class="ux-stepper-panel is-active" id="compliance-step-policies" data-stepper-panel>
<div class="row g-4">
    <div class="col-12" data-aos="fade-up">
        <div class="glass-panel h-100">
            <h4 class="mb-3">Policy Document Management</h4>
            <form method="POST" action="<?= route_url('admin/compliance/policy-save') ?>" class="row g-3 mb-4 cms-form">
                <?= csrf_field() ?>
                <input type="hidden" name="policy_id" value="">
                <div class="col-md-8"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Category</label><select name="category" class="form-select"><option value="privacy_policy">Privacy Policy</option><option value="terms_of_service">Terms of Service</option><option value="hipaa_notice">HIPAA Notice</option><option value="gdpr_rights">GDPR Rights</option><option value="security_policy">Security Policy</option></select></div>
                <div class="col-md-4"><label class="form-label">Slug</label><input type="text" name="slug" class="form-control" placeholder="auto-from-title"></div>
                <div class="col-md-4"><label class="form-label">Version</label><input type="text" name="version_label" class="form-control" placeholder="v1.0"></div>
                <div class="col-md-4"><label class="form-label">Effective Date</label><input type="date" name="effective_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                <div class="col-md-6"><label class="preference-check"><input type="checkbox" name="requires_acknowledgement" value="1"> <span>Require user acknowledgement</span></label></div>
                <div class="col-md-6"><label class="preference-check"><input type="checkbox" name="is_public" value="1" checked> <span>Visible on public site</span></label></div>
                <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="published">Published</option><option value="draft">Draft</option></select></div>
                <div class="col-12"><label class="form-label">Policy Content</label><textarea name="content" class="form-control rich-editor-source" rows="10" data-rich-editor required></textarea></div>
                <div class="col-12 d-grid"><button class="btn btn-primary">Save Policy</button></div>
            </form>
            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Policy</th><th>Version</th><th>Status</th><th>Acks</th></tr></thead><tbody><?php foreach (($policies ?? []) as $policy): ?><tr><td><?= e($policy['title']) ?><div class="small text-muted">/<?= e($policy['slug']) ?></div></td><td><?= e($policy['version_label']) ?></td><td><span class="badge text-bg-light text-capitalize"><?= e($policy['status']) ?></span></td><td><?= (int) ($policyAcks[(int) $policy['id']] ?? 0) ?></td></tr><?php endforeach; ?><?php if (empty($policies)): ?><tr><td colspan="4" class="text-center text-muted py-4">No policy documents created yet. Add privacy, terms, security, and acknowledgement policies for users to review.</td></tr><?php endif; ?></tbody></table></div>
        </div>
    </div>
    </div>
        </section>
        <section class="ux-stepper-panel" id="compliance-step-incidents" data-stepper-panel>
<div class="row g-4">

    <div class="col-12" data-aos="fade-up">
        <div class="glass-panel h-100">
            <h4 class="mb-3">Compliance Incident Log</h4>
            <form method="POST" action="<?= route_url('admin/compliance/incident-save') ?>" class="row g-3 mb-4">
                <?= csrf_field() ?>
                <div class="col-md-6"><label class="form-label">Incident Title</label><input type="text" name="title" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Type</label><select name="incident_type" class="form-select"><option value="policy">Policy</option><option value="privacy">Privacy</option><option value="security">Security</option><option value="breach">Breach</option><option value="access">Access</option></select></div>
                <div class="col-md-3"><label class="form-label">Severity</label><select name="severity" class="form-select"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div>
                <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="open">Open</option><option value="investigating">Investigating</option><option value="resolved">Resolved</option><option value="closed">Closed</option></select></div>
                <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4"></textarea></div>
                <div class="col-12"><label class="form-label">Action Taken</label><textarea name="action_taken" class="form-control" rows="3"></textarea></div>
                <div class="col-12 d-grid"><button class="btn btn-primary">Log Incident</button></div>
            </form>
            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Incident</th><th>Severity</th><th>Status</th><th>Reported</th></tr></thead><tbody><?php foreach (($incidents ?? []) as $incident): ?><tr><td><?= e($incident['title']) ?><div class="small text-muted text-capitalize"><?= e($incident['incident_type']) ?></div></td><td><span class="badge text-bg-light text-capitalize"><?= e($incident['severity']) ?></span></td><td><span class="badge text-bg-light text-capitalize"><?= e($incident['status']) ?></span></td><td><?= e($incident['created_at']) ?></td></tr><?php endforeach; ?><?php if (empty($incidents)): ?><tr><td colspan="4" class="text-center text-muted py-4">No incidents logged yet. Security, privacy, access, and policy issues will appear here for follow-up.</td></tr><?php endif; ?></tbody></table></div>
        </div>
    </div>
</div>

        </section>
        <section class="ux-stepper-panel" id="compliance-step-requests" data-stepper-panel>
<div class="glass-panel mt-1" data-aos="fade-up">
    <h4 class="mb-3">Privacy / Data Rights Requests</h4>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>User</th><th>Type</th><th>Status</th><th>Description</th><th>Update</th></tr></thead><tbody><?php foreach (($requests ?? []) as $request): ?><tr><td><?= e($request['requester_email']) ?><div class="small text-muted"><?= e($request['created_at']) ?></div></td><td class="text-capitalize"><?= e($request['request_type']) ?></td><td><span class="badge text-bg-light text-capitalize"><?= e($request['status']) ?></span></td><td><?= e($request['description']) ?></td><td><form method="POST" action="<?= route_url('admin/compliance/request-update') ?>" class="d-grid gap-2"><?= csrf_field() ?><input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>"><select name="status" class="form-select form-select-sm"><option value="submitted" <?= $request['status'] === 'submitted' ? 'selected' : '' ?>>Submitted</option><option value="in_review" <?= $request['status'] === 'in_review' ? 'selected' : '' ?>>In Review</option><option value="completed" <?= $request['status'] === 'completed' ? 'selected' : '' ?>>Completed</option><option value="rejected" <?= $request['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option></select><input type="text" name="admin_notes" class="form-control form-control-sm" placeholder="Admin notes"><button class="btn btn-outline-primary btn-sm">Save</button></form></td></tr><?php endforeach; ?><?php if (empty($requests)): ?><tr><td colspan="5" class="text-center text-muted py-4">No data rights requests yet. Patient access, correction, export, deletion, and restriction requests will appear here.</td></tr><?php endif; ?></tbody></table></div>
</div>
        </section>
    </div>
</div>
