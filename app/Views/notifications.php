<section class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <span class="eyebrow">Notification center</span>
        <h1 class="mb-0">Notifications</h1>
        <p class="text-muted mb-0 mt-2">Review account alerts, care updates, billing notices, and security messages in one place.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <span class="badge text-bg-light px-3 py-2">Unread: <?= (int) ($unreadCount ?? 0) ?></span>
        <form method="POST" action="<?= route_url('notifications/mark-all-read') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-outline-primary btn-sm">Mark all as read</button>
        </form>
        <?php if (App\Core\Auth::type() === 'admin'): ?>
            <a href="<?= route_url('admin/communications') ?>" class="btn btn-primary btn-sm">Manage Templates</a>
        <?php endif; ?>
    </div>
</section>

<div class="ux-stepper" data-stepper>
    <div class="ux-stepper-nav" role="tablist" aria-label="Notification sections">
        <button class="ux-stepper-tab is-active" data-stepper-tab data-stepper-target="notifications-step-feed">
            <span class="ux-stepper-number">1</span>
            <span class="ux-stepper-label"><strong>Feed</strong><span>Recent alerts</span></span>
        </button>
        <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="notifications-step-preferences">
            <span class="ux-stepper-number">2</span>
            <span class="ux-stepper-label"><strong>Preferences</strong><span>Delivery settings</span></span>
        </button>
    </div>
    <div class="ux-stepper-panels">
        <section class="ux-stepper-panel is-active" id="notifications-step-feed" data-stepper-panel>
<div class="row g-4">
    <div class="col-12" data-aos="fade-up">
        <div class="glass-panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Recent notifications</h4>
                <span class="small text-muted">AJAX polling updates the bell in the header</span>
            </div>
            <div class="notification-feed">
                <?php foreach (($notifications ?? []) as $notification): ?>
                    <div class="notification-row <?= empty($notification['is_read']) ? 'unread' : '' ?>">
                        <div class="notification-icon type-<?= e($notification['type']) ?>"><i class="fa-solid fa-bell"></i></div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between gap-3 flex-wrap">
                                <div>
                                    <h6 class="mb-1"><?= e($notification['title']) ?></h6>
                                    <div class="text-muted small mb-1"><?= e($notification['message']) ?></div>
                                    <div class="small text-muted"><?= e($notification['created_at']) ?></div>
                                </div>
                                <div class="d-flex gap-2 flex-wrap align-items-start">
                                    <?php if (!empty($notification['action_url'])): ?>
                                        <a class="btn btn-outline-primary btn-sm" href="<?= e($notification['action_url']) ?>">Open</a>
                                    <?php endif; ?>
                                    <?php if (empty($notification['is_read'])): ?>
                                        <form method="POST" action="<?= route_url('notifications/mark-read') ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="notification_id" value="<?= (int) $notification['id'] ?>">
                                            <button class="btn btn-primary btn-sm">Mark read</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge text-bg-light">Read</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($notifications)): ?><div class="text-muted">No notifications yet. Appointment changes, billing activity, security alerts, and care updates will appear here.</div><?php endif; ?>
            </div>
        </div>
    </div>
    </div>
        </section>
        <section class="ux-stepper-panel" id="notifications-step-preferences" data-stepper-panel>
<div class="row g-4">

    <div class="col-12" data-aos="fade-up">
        <div class="glass-panel h-100">
            <h4>Preferences</h4>
            <form method="POST" action="<?= route_url('notifications/preferences') ?>" class="row g-3 mt-2">
                <?= csrf_field() ?>
                <?php
                $checks = [
                    'in_app_enabled' => 'In-app notifications',
                    'email_enabled' => 'Email notifications',
                    'sms_enabled' => 'SMS notifications',
                    'appointment_updates' => 'Appointment updates',
                    'payment_updates' => 'Payment and invoice updates',
                    'security_updates' => 'Security and account alerts',
                    'marketing_updates' => 'News and marketing updates',
                ];
                foreach ($checks as $field => $label): ?>
                    <div class="col-12">
                        <label class="preference-check">
                            <input type="checkbox" name="<?= e($field) ?>" <?= !empty($preferences[$field]) ? 'checked' : '' ?>>
                            <span><?= e($label) ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
                <div class="col-12">
                    <label class="form-label">Preferred language</label>
                    <select name="preferred_language" class="form-select">
                        <?php foreach (['en' => 'English', 'ur' => 'Urdu', 'ar' => 'Arabic', 'es' => 'Spanish', 'fr' => 'French', 'zh' => 'Chinese'] as $code => $label): ?>
                            <option value="<?= e($code) ?>" <?= ($preferences['preferred_language'] ?? 'en') === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 d-grid"><button class="btn btn-primary">Save Preferences</button></div>
            </form>
            <div class="small text-muted mt-3">SMS delivery uses Twilio credentials if configured and installed. Email uses PHPMailer when available, otherwise falls back to PHP mail().</div>
        </div>
    </div>
</div>
        </section>
    </div>
</div>
