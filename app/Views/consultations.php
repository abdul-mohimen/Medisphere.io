<?php
$role = $role ?? '';
$consultationContacts = $consultationContacts ?? [];
$subscriptionLocked = !empty($subscriptionLocked);
$phoneMeta = static function (?string $phone): array {
    $raw = trim((string) $phone);
    if ($raw === '' || preg_match('/^not listed$/i', $raw)) {
        return ['raw' => '', 'tel' => ''];
    }
    $tel = preg_replace('/[^\d+]/', '', $raw) ?: '';
    $digits = preg_replace('/\D+/', '', $raw) ?: '';
    return ['raw' => $raw, 'tel' => strlen($digits) >= 7 ? 'tel:' . $tel : ''];
};
$initials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice(array_filter($parts), 0, 2) as $part) {
        $letters .= strtoupper(substr($part, 0, 1));
    }
    return $letters ?: 'MS';
};
$roleLine = match ($role) {
    'patient' => 'Patients can message doctors and open voice or video rooms from eligible doctor appointments.',
    'doctor' => 'Doctors can coordinate with patients and the hospital care desk from one consultation hub.',
    'hospital' => 'Hospitals can coordinate directly with assigned doctors for care operations.',
    default => 'Consultation monitoring is focused on patient, doctor, and hospital care workflows.',
};
?>

<section class="consultation-page-hero mb-4" data-aos="fade-up">
    <div>
        <span class="eyebrow">Telemedicine</span>
        <h1 class="mb-0">Voice &amp; Video Consultation</h1>
        <p class="text-muted mb-0 mt-2"><?= e($roleLine) ?></p>
    </div>
    <div class="consultation-hero-actions">
        <a href="<?= route_url('messages') ?>" class="btn btn-light btn-sm"><i class="fa-solid fa-comments"></i> Messages</a>
        <a href="<?= route_url('appointments') ?>" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-calendar-check"></i> Appointments</a>
    </div>
</section>

<?php if ($subscriptionLocked): ?>
    <section class="premium-inline-banner mb-4" data-aos="fade-up">
        <div>
            <span class="eyebrow">Premium video access</span>
            <h2>Subscribe to open live consultation rooms</h2>
            <p>Video, voice, screen sharing, and consultation media tools are available after premium activation.</p>
        </div>
        <a class="btn btn-primary" href="<?= e($subscriptionGate['subscription_page_url'] ?? route_url('payments/subscriptions', ['return_to' => route_url('consultations')])) ?>">
            <i class="fa-solid fa-crown"></i>
            Unlock Video
        </a>
    </section>
<?php endif; ?>

<?php if ($role === 'doctor'): ?>
    <div class="glass-panel mb-4" data-aos="fade-up">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="mb-1">Availability Status</h4>
                <div class="small text-muted">Let patients know whether you are ready for live consultation.</div>
            </div>
            <form method="POST" action="<?= route_url('consultations/availability') ?>" class="d-flex gap-2 flex-wrap align-items-center">
                <?= csrf_field() ?>
                <select name="availability_status" class="form-select w-auto">
                    <?php foreach (['online', 'busy', 'offline'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= ($availability['availability_status'] ?? 'offline') === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary btn-sm">Update</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<section class="consultation-command-hub glass-panel mb-4" data-aos="fade-up">
    <div class="consultation-phone-preview">
        <div class="consult-phone-topbar">
            <span><i class="fa-solid fa-lock"></i> MediSphere secure chat</span>
            <div><i class="fa-solid fa-phone"></i><i class="fa-solid fa-video"></i></div>
        </div>
        <div class="consult-chat-thread">
            <div class="consult-chat-bubble theirs">Ready for the consultation update?</div>
            <div class="consult-chat-bubble mine">Yes, reports and symptoms are attached.</div>
            <div class="consult-voice-note"><i class="fa-solid fa-microphone"></i><span></span><small>0:18</small></div>
        </div>
        <div class="consult-phone-composer">
            <span>Type a message...</span>
            <i class="fa-solid fa-paper-plane"></i>
        </div>
    </div>

    <div class="consultation-contact-panel">
        <div class="consultation-contact-head">
            <div>
                <span class="eyebrow">Care contacts</span>
                <h2>Message, voice, or video</h2>
            </div>
            <span class="badge text-bg-light"><?= count($consultationContacts) ?> available</span>
        </div>

        <div class="consultation-contact-grid">
            <?php foreach ($consultationContacts as $contact): ?>
                <?php
                $phone = $phoneMeta($contact['phone'] ?? '');
                $canStartRoom = (int) ($contact['appointment_id'] ?? 0) > 0 && in_array($role, ['patient', 'doctor'], true);
                $messageUrl = $contact['message_url'] ?? route_url('messages', ['contact_id' => (int) ($contact['id'] ?? 0)]);
                $fallbackUrl = $contact['fallback_url'] ?? $messageUrl;
                $videoFallbackLabel = $role === 'patient' ? 'Book' : 'Video';
                ?>
                <article class="consult-contact-card">
                    <div class="consult-contact-main">
                        <div class="consult-contact-avatar type-<?= e($contact['type'] ?? 'doctor') ?>"><?= e($initials((string) ($contact['name'] ?? 'Care'))) ?></div>
                        <div>
                            <span class="consult-contact-type"><?= e($contact['type'] ?? 'contact') ?></span>
                            <h3><?= e($contact['name'] ?? 'Care contact') ?></h3>
                            <p><?= e($contact['subtitle'] ?? 'Care coordination') ?></p>
                            <small><?= e($contact['meta'] ?? ($contact['status'] ?? 'Available')) ?></small>
                        </div>
                    </div>
                    <div class="consult-contact-actions">
                        <a class="consult-action message" href="<?= e($messageUrl) ?>"><i class="fa-solid fa-message"></i><span>Message</span></a>
                        <?php if ($canStartRoom): ?>
                            <form method="POST" action="<?= route_url('consultations/create') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="appointment_id" value="<?= (int) $contact['appointment_id'] ?>">
                                <button class="consult-action voice" type="submit" name="call_type" value="voice"><i class="fa-solid fa-phone"></i><span>Voice</span></button>
                            </form>
                            <form method="POST" action="<?= route_url('consultations/create') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="appointment_id" value="<?= (int) $contact['appointment_id'] ?>">
                                <button class="consult-action video" type="submit" name="call_type" value="video"><i class="fa-solid fa-video"></i><span>Video</span></button>
                            </form>
                        <?php else: ?>
                            <a class="consult-action voice <?= $phone['tel'] ? '' : 'muted' ?>" href="<?= e($phone['tel'] ?: $messageUrl) ?>"><i class="fa-solid fa-phone"></i><span><?= $phone['tel'] ? 'Voice' : 'Voice note' ?></span></a>
                            <a class="consult-action video" href="<?= e($fallbackUrl) ?>"><i class="fa-solid fa-video"></i><span><?= e($videoFallbackLabel) ?></span></a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if (!$consultationContacts): ?>
                <div class="consultation-rich-empty">
                    <i class="fa-solid fa-user-doctor"></i>
                    <div>
                        <strong>No contacts are connected yet</strong>
                        <span><?= $role === 'patient' ? 'Find and book a doctor to open voice and video consultation.' : 'Connected care contacts will appear here after appointments or hospital assignments.' ?></span>
                    </div>
                    <a href="<?= $role === 'patient' ? route_url('find-healthcare') : route_url('messages') ?>" class="btn btn-outline-primary btn-sm"><?= $role === 'patient' ? 'Find doctors' : 'Open messages' ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="ux-stepper" data-stepper>
    <div class="ux-stepper-nav" role="tablist" aria-label="Consultation sections">
        <button class="ux-stepper-tab is-active" data-stepper-tab data-stepper-target="consultation-step-live">
            <span class="ux-stepper-number">1</span>
            <span class="ux-stepper-label"><strong>Live Flow</strong><span>Queue and appointments</span></span>
        </button>
        <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="consultation-step-history">
            <span class="ux-stepper-number">2</span>
            <span class="ux-stepper-label"><strong>History</strong><span>Past and active rooms</span></span>
        </button>
    </div>
    <div class="ux-stepper-panels">
        <section class="ux-stepper-panel is-active" id="consultation-step-live" data-stepper-panel>
<div class="row g-4">
    <div class="col-12" data-aos="fade-up">
        <div class="glass-panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><?= $role === 'doctor' ? 'Waiting Room Queue' : ($role === 'hospital' ? 'Doctor Coordination' : 'Eligible Appointments') ?></h4>
                <span class="small text-muted"><?= $role === 'doctor' ? count($waitingSessions ?? []) . ' waiting' : ($role === 'hospital' ? 'Message assigned doctors' : 'Start a room from an appointment') ?></span>
            </div>

            <?php if ($role === 'patient'): ?>
                <div class="consultation-list">
                    <?php foreach (($appointments ?? []) as $appointment): ?>
                        <div class="consultation-card call-ready-card">
                            <div class="call-ready-main">
                                <div class="call-ready-avatar"><i class="fa-solid fa-user-doctor"></i></div>
                                <div>
                                    <h6>Dr. <?= e($appointment['doctor_name'] ?? 'Doctor') ?></h6>
                                    <p><?= e($appointment['specialization'] ?? 'General') ?></p>
                                    <span><?= e($appointment['date'] ?? '') ?> at <?= e($appointment['time'] ?? '') ?></span>
                                    <small class="badge text-bg-light text-capitalize"><?= e($appointment['status'] ?? 'pending') ?></small>
                                </div>
                            </div>
                            <div class="call-ready-actions">
                                <form method="POST" action="<?= route_url('consultations/create') ?>" class="consultation-start-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="appointment_id" value="<?= (int) $appointment['id'] ?>">
                                    <label class="call-consent-toggle"><input type="checkbox" name="consent_recording" value="1"> <span>Recording consent</span></label>
                                    <?php if (!empty($subscriptionLocked)): ?>
                                        <a class="btn btn-primary btn-sm" href="<?= e($subscriptionGate['subscription_page_url'] ?? route_url('payments/subscriptions', ['return_to' => route_url('consultations')])) ?>">
                                            <i class="fa-solid fa-lock"></i> Unlock Room
                                        </a>
                                    <?php else: ?>
                                        <div class="call-start-buttons" aria-label="Start consultation">
                                            <button type="submit" name="call_type" value="voice" title="Start voice call" aria-label="Start voice call"><i class="fa-solid fa-phone"></i><span>Voice</span></button>
                                            <button type="submit" name="call_type" value="video" title="Start video call" aria-label="Start video call"><i class="fa-solid fa-video"></i><span>Video</span></button>
                                        </div>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($appointments)): ?>
                        <div class="consultation-rich-empty">
                            <i class="fa-solid fa-calendar-plus"></i>
                            <div><strong>No appointment room yet</strong><span>Use the contacts above to message or book a doctor. Confirmed bookings will open voice and video rooms here.</span></div>
                            <a href="<?= route_url('find-healthcare') ?>" class="btn btn-outline-primary btn-sm">Find doctors</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($role === 'doctor'): ?>
                <div class="consultation-list">
                    <?php foreach (($waitingSessions ?? []) as $session): ?>
                        <div class="consultation-card waiting">
                            <div class="call-ready-main">
                                <div class="call-ready-avatar"><i class="fa-solid fa-user-injured"></i></div>
                                <div>
                                    <h6><?= e($session['patient_name'] ?? 'Patient') ?></h6>
                                    <p>Waiting since <?= e($session['created_at']) ?></p>
                                    <span><?= e($session['appointment_date'] ?? '') ?> at <?= e($session['appointment_time'] ?? '') ?></span>
                                </div>
                            </div>
                            <a href="<?= route_url('consultations/room', ['id' => (int) $session['id']]) ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-phone-volume"></i> Join Room</a>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($waitingSessions)): ?>
                        <div class="consultation-rich-empty">
                            <i class="fa-solid fa-headset"></i>
                            <div><strong>No patient is waiting right now</strong><span>Use the contacts above to message patients or coordinate with your hospital while the queue is quiet.</span></div>
                            <a href="<?= route_url('messages') ?>" class="btn btn-outline-primary btn-sm">Open messages</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($role === 'hospital'): ?>
                <div class="consultation-mini-grid">
                    <?php foreach ($consultationContacts as $contact): ?>
                        <a class="consultation-mini-card" href="<?= e($contact['message_url'] ?? route_url('messages')) ?>">
                            <i class="fa-solid fa-user-doctor"></i>
                            <strong><?= e($contact['name'] ?? 'Doctor') ?></strong>
                            <span><?= e($contact['subtitle'] ?? 'Doctor coordination') ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (!$consultationContacts): ?>
                        <div class="consultation-rich-empty">
                            <i class="fa-solid fa-hospital-user"></i>
                            <div><strong>No assigned doctors yet</strong><span>Approve doctor affiliations from Hospital Management, then consultation contacts will appear here.</span></div>
                            <a href="<?= route_url('hospital-management') ?>" class="btn btn-outline-primary btn-sm">Hospital management</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="text-muted"><?= e($message ?? 'Consultation management is available to doctors and patients.') ?></div>
            <?php endif; ?>
        </div>
    </div>
    </div>
        </section>
        <section class="ux-stepper-panel" id="consultation-step-history" data-stepper-panel>
<div class="row g-4">

    <div class="col-12" data-aos="fade-up">
        <div class="glass-panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Call History</h4>
                <span class="small text-muted">Waiting, active, and completed room records</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Room</th><th>Participants</th><th>Type</th><th>Status</th><th>Created</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach (($sessions ?? []) as $session): ?>
                        <tr>
                            <td><?= e($session['room_name']) ?><div class="small text-muted">#<?= (int) $session['id'] ?></div></td>
                            <td>
                                <div><?= e($session['patient_name'] ?? 'Patient') ?></div>
                                <div class="small text-muted">Dr. <?= e($session['doctor_name'] ?? 'Doctor') ?></div>
                            </td>
                            <td class="text-capitalize"><?= e($session['call_type']) ?></td>
                            <td><span class="badge text-bg-light text-capitalize"><?= e($session['status']) ?></span></td>
                            <td><?= e($session['created_at']) ?></td>
                            <td>
                                <a href="<?= route_url('consultations/room', ['id' => (int) $session['id']]) ?>" class="btn btn-outline-primary btn-sm">
                                    <?= in_array($session['status'], ['waiting', 'active'], true) ? 'Open Room' : 'View Summary' ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($sessions)): ?><tr><td colspan="6" class="text-center text-muted py-4">No consultation sessions found yet. Waiting, active, and completed video or voice rooms will be listed here.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
        </section>
    </div>
</div>
