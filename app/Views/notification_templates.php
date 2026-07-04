<section class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <span class="eyebrow">Admin communications</span>
        <h1 class="mb-0">Email & SMS Templates</h1>
        <p class="text-muted mb-0 mt-2">Shape clear appointment, billing, security, and care updates with reusable communication templates.</p>
    </div>
    <a href="<?= route_url('notifications') ?>" class="btn btn-outline-primary">Back to Notifications</a>
</section>

<div class="ux-stepper" data-stepper>
    <div class="ux-stepper-nav" role="tablist" aria-label="Communication template sections">
        <button class="ux-stepper-tab is-active" data-stepper-tab data-stepper-target="templates-step-email">
            <span class="ux-stepper-number">1</span>
            <span class="ux-stepper-label"><strong>Email</strong><span>Rich message templates</span></span>
        </button>
        <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="templates-step-sms">
            <span class="ux-stepper-number">2</span>
            <span class="ux-stepper-label"><strong>SMS</strong><span>Short mobile alerts</span></span>
        </button>
    </div>
    <div class="ux-stepper-panels">
        <section class="ux-stepper-panel is-active" id="templates-step-email" data-stepper-panel>
<div class="row g-4">
    <div class="col-12" data-aos="fade-up">
        <div class="glass-panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Email templates</h4>
                <span class="small text-muted">Use placeholders like <code>{{name}}</code>, <code>{{amount}}</code>, <code>{{transaction_reference}}</code></span>
            </div>
            <div class="template-list-grid">
                <?php foreach (($emailTemplates ?? []) as $template): ?>
                    <div class="template-editor-card">
                        <form method="POST" action="<?= route_url('admin/communications/email-save') ?>" class="row g-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="template_key" value="<?= e($template['template_key']) ?>">
                            <div class="col-12"><label class="form-label">Template Key</label><input type="text" class="form-control" value="<?= e($template['template_key']) ?>" disabled></div>
                            <div class="col-12"><label class="form-label">Subject</label><input type="text" name="subject" class="form-control" value="<?= e($template['subject']) ?>"></div>
                            <div class="col-12"><label class="form-label">HTML Body</label><textarea name="body_html" class="form-control" rows="5"><?= e($template['body_html']) ?></textarea></div>
                            <div class="col-12"><label class="form-label">Text Body</label><textarea name="body_text" class="form-control" rows="3"><?= e($template['body_text']) ?></textarea></div>
                            <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" <?= $template['status'] === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= $template['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option></select></div>
                            <div class="col-md-6 d-grid align-items-end"><button class="btn btn-primary">Save Email Template</button></div>
                        </form>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($emailTemplates)): ?><div class="text-muted">No email templates available yet. Create templates for appointment reminders, billing receipts, verification alerts, and security updates.</div><?php endif; ?>
                <div class="template-editor-card">
                    <form method="POST" action="<?= route_url('admin/communications/email-save') ?>" class="row g-2">
                        <?= csrf_field() ?>
                        <div class="col-12"><label class="form-label">New Template Key</label><input type="text" name="template_key" class="form-control" placeholder="custom_template_key" required></div>
                        <div class="col-12"><label class="form-label">Subject</label><input type="text" name="subject" class="form-control" required></div>
                        <div class="col-12"><label class="form-label">HTML Body</label><textarea name="body_html" class="form-control" rows="4" required></textarea></div>
                        <div class="col-12"><label class="form-label">Text Body</label><textarea name="body_text" class="form-control" rows="3" required></textarea></div>
                        <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                        <div class="col-md-6 d-grid align-items-end"><button class="btn btn-outline-primary">Create Email Template</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>
        </section>
        <section class="ux-stepper-panel" id="templates-step-sms" data-stepper-panel>
<div class="row g-4">

    <div class="col-12" data-aos="fade-up">
        <div class="glass-panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">SMS templates</h4>
                <span class="small text-muted">Keep SMS short for deliverability.</span>
            </div>
            <div class="template-list-grid">
                <?php foreach (($smsTemplates ?? []) as $template): ?>
                    <div class="template-editor-card">
                        <form method="POST" action="<?= route_url('admin/communications/sms-save') ?>" class="row g-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="template_key" value="<?= e($template['template_key']) ?>">
                            <div class="col-12"><label class="form-label">Template Key</label><input type="text" class="form-control" value="<?= e($template['template_key']) ?>" disabled></div>
                            <div class="col-12"><label class="form-label">SMS Body</label><textarea name="body_text" class="form-control" rows="4"><?= e($template['body_text']) ?></textarea></div>
                            <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" <?= $template['status'] === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= $template['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option></select></div>
                            <div class="col-md-6 d-grid align-items-end"><button class="btn btn-primary">Save SMS Template</button></div>
                        </form>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($smsTemplates)): ?><div class="text-muted">No SMS templates available yet. Add concise mobile messages for urgent care updates and time-sensitive confirmations.</div><?php endif; ?>
                <div class="template-editor-card">
                    <form method="POST" action="<?= route_url('admin/communications/sms-save') ?>" class="row g-2">
                        <?= csrf_field() ?>
                        <div class="col-12"><label class="form-label">New Template Key</label><input type="text" name="template_key" class="form-control" placeholder="custom_template_key" required></div>
                        <div class="col-12"><label class="form-label">SMS Body</label><textarea name="body_text" class="form-control" rows="4" required></textarea></div>
                        <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                        <div class="col-md-6 d-grid align-items-end"><button class="btn btn-outline-primary">Create SMS Template</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
        </section>
    </div>
</div>
