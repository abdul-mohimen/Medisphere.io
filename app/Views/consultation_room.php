<?php
$subscriptionLocked = !empty($subscriptionLocked);
$remoteDisplayName = ($selfRole ?? 'patient') === 'doctor'
    ? (string) ($session['patient_name'] ?? 'Patient')
    : 'Dr. ' . (string) ($session['doctor_name'] ?? 'Doctor');
$remoteInitial = strtoupper(substr($remoteDisplayName, 0, 1));
$callType = (string) ($session['call_type'] ?? 'video');
?>
<section class="consultation-page-hero mb-4" data-aos="fade-up">
    <div class="consultation-hero-copy">
        <span class="eyebrow">Live Medical Session</span>
        <h1 class="mb-0"><?= e($session['room_name'] ?? 'Consultation Room') ?></h1>
        <p class="text-muted mb-0 mt-2">Professional, secure video and chat consultation. Your communication is end-to-end encrypted during the session.</p>
        <div class="mt-3 d-flex gap-2 flex-wrap align-items-center">
            <span class="badge text-bg-light text-capitalize">Status: <?= e($session['status'] ?? 'waiting') ?></span>
            <a href="<?= route_url('consultations') ?>" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back to Hub
            </a>
        </div>
    </div>
</section>

<?php if ($subscriptionLocked): ?>
    <section class="premium-inline-banner mb-4" data-aos="fade-up">
        <div>
            <span class="eyebrow">Premium live media</span>
            <h2>Room details are visible. Live audio, video, screen sharing, and recording need Premium.</h2>
            <p>You can review the appointment, participants, status, and feedback. Subscribe only when you want to start the live consultation media.</p>
        </div>
        <button class="btn btn-primary" type="button" data-premium-locked="1">
            <i class="fa-solid fa-crown"></i>
            Unlock Live Room
        </button>
    </section>
<?php endif; ?>

<div class="consultation-room-shell glass-panel overflow-hidden" data-aos="fade-up">
    <div id="consultationApp" class="consultation-call-shell"
         data-session-id="<?= (int) $session['id'] ?>"
         data-self-role="<?= e($selfRole ?? 'patient') ?>"
         data-call-type="<?= e($session['call_type'] ?? 'video') ?>"
         data-initiator-id="<?= (int) ($session['initiator_id'] ?? 0) ?>"
         data-consent-recording="<?= !empty($session['consent_recording']) ? '1' : '0' ?>"
         data-premium-locked="<?= $subscriptionLocked ? '1' : '0' ?>"
         data-status="<?= e($session['status'] ?? 'waiting') ?>">

        <header class="consultation-call-topbar">
            <div class="call-contact">
                <div class="call-contact-avatar"><?= e($remoteInitial ?: 'P') ?></div>
                <div>
                    <strong><?= e($remoteDisplayName) ?></strong>
                    <span id="remoteConnectionState">Waiting for connection...</span>
                </div>
            </div>
            <div class="call-topbar-actions">
                <span class="call-pill text-capitalize"><i class="fa-solid fa-signal"></i>Status: <span id="consultationStatusLabel"><?= e($session['status'] ?? 'waiting') ?></span></span>
                <span class="call-pill text-capitalize"><i class="fa-solid <?= $callType === 'voice' ? 'fa-phone' : 'fa-video' ?>"></i><?= e($callType) ?></span>
                <?php if (!empty($session['consent_recording'])): ?><span class="call-pill"><i class="fa-solid fa-record-vinyl"></i>Recording consented</span><?php endif; ?>
            </div>
        </header>

        <div class="consultation-call-layout">
            <section class="consultation-stage">
                <!-- Pinned Top Contact Info -->
                <div class="pinned-admin-topbar">
                    <div class="pinned-contact">
                        <div class="pinned-avatar"><?= e($remoteInitial ?: 'P') ?></div>
                        <div>
                            <strong><?= e($remoteDisplayName) ?></strong>
                            <small id="remoteConnectionState">Waiting for connection...</small>
                        </div>
                    </div>
                </div>

                <div class="video-stage-grid <?= $callType === 'voice' ? 'voice-only' : '' ?>">
                    <div class="video-card remote-card">
                        <video id="remoteVideo" autoplay playsinline></video>
                        <div id="waitingRoomMessage" class="waiting-room-status video-waiting-message">
                            <span class="pulse-dot"></span>
                            <span>Connecting securely...</span>
                        </div>
                        <div class="video-overlay" hidden>
                            <strong><?= e($remoteDisplayName) ?></strong>
                            <span>Remote consultation feed</span>
                        </div>
                    </div>

                    <div class="video-card local-card">
                        <video id="localVideo" autoplay muted playsinline></video>
                        <div class="video-overlay" hidden>
                            <strong>You</strong>
                            <span id="localMediaState">Camera and microphone idle</span>
                        </div>
                    </div>
                </div>

                <!-- Pinned Bottom Controls -->
                <div class="pinned-admin-controls">
                    <div class="consultation-controls">
                        <button type="button" class="call-control" id="toggleMicBtn" title="Mute microphone" aria-label="Mute microphone"><i class="fa-solid fa-microphone"></i></button>
                        <?php if ($callType !== 'voice'): ?>
                            <button type="button" class="call-control" id="toggleCameraBtn" title="Toggle camera" aria-label="Toggle camera"><i class="fa-solid fa-video"></i></button>
                        <?php endif; ?>
                        <button type="button" class="call-control" id="shareScreenBtn" title="Share screen" aria-label="Share screen"><i class="fa-solid fa-display"></i></button>
                        <button type="button" class="call-control" id="recordSessionBtn" title="Record locally" aria-label="Record locally"><i class="fa-solid fa-record-vinyl"></i></button>
                        <button type="button" class="call-control danger" id="hangupBtn" title="End call" aria-label="End call"><i class="fa-solid fa-phone-slash"></i></button>
                    </div>
                </div>
            </section>

            <aside class="consultation-call-sidebar">
                <div class="consultation-info-card">
                    <h5>Session Details</h5>
                    <div class="session-meta-list">
                        <div><span>Room</span><strong><?= e($session['room_name'] ?? 'Consultation Room') ?></strong></div>
                        <div><span>Patient</span><strong><?= e($session['patient_name'] ?? 'Patient') ?></strong></div>
                        <div><span>Doctor</span><strong>Dr. <?= e($session['doctor_name'] ?? 'Doctor') ?></strong></div>
                        <div><span>Started</span><strong><?= e($session['started_at'] ?? 'Waiting') ?></strong></div>
                    </div>
                </div>

                <div class="consultation-info-card whatsapp-style-chat">
                    <div class="whatsapp-chat-header">
                        <div class="whatsapp-header-info">
                            <i class="fa-solid fa-comments"></i>
                            <div>
                                <strong>Live Chat</strong>
                                <span>Uses secure signaling</span>
                            </div>
                        </div>
                    </div>
                    <div class="whatsapp-chat-messages" id="chatMessagesArea" aria-live="polite">
                        <div class="whatsapp-chat-message system">Chat is available while both participants keep this room open.</div>
                    </div>
                    <div class="whatsapp-chat-composer">
                        <input type="text" id="chatInputMessage" class="whatsapp-input" maxlength="700" placeholder="Type a message..." aria-label="Type a consultation chat message">
                        <button type="button" id="sendChatBtn" class="whatsapp-send-btn" aria-label="Send chat message"><i class="fa-solid fa-paper-plane"></i></button>
                    </div>
                </div>

                <div class="consultation-info-card">
                    <h5>Connection Log</h5>
                    <div class="connection-log" id="connectionLog">
                        <div class="log-item">Room initialized. Waiting for media permissions and participant presence.</div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<?php if (($session['status'] ?? '') === 'ended' && empty($myFeedback)): ?>
    <div class="glass-panel mt-4" data-aos="fade-up">
        <h4 class="mb-3">Rate this consultation</h4>
        <form method="POST" action="<?= route_url('consultations/feedback') ?>" class="row g-3">
            <?= csrf_field() ?>
            <input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">
            <div class="col-md-3">
                <label class="form-label">Rating</label>
                <select name="rating" class="form-select">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <option value="<?= $i ?>"><?= $i ?> Star<?= $i > 1 ? 's' : '' ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-9">
                <label class="form-label">Review</label>
                <textarea name="review_text" class="form-control" rows="3" placeholder="Share your consultation experience"></textarea>
            </div>
            <div class="col-12"><button class="btn btn-primary">Submit Feedback</button></div>
        </form>
    </div>
<?php endif; ?>

<div class="glass-panel mt-4" data-aos="fade-up">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Consultation Feedback</h4>
        <form method="POST" action="<?= route_url('consultations/end') ?>" onsubmit="return confirm('End this consultation session?');">
            <?= csrf_field() ?>
            <input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">
            <?php if (($session['status'] ?? '') !== 'ended'): ?><button class="btn btn-outline-danger btn-sm">End Session from Server</button><?php endif; ?>
        </form>
    </div>
    <div class="row g-3">
        <?php foreach (($feedback ?? []) as $item): ?>
            <div class="col-md-6">
                <div class="feedback-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong><?= e($item['reviewer_email']) ?></strong>
                        <span class="badge text-bg-light"><?= (int) $item['rating'] ?>/5</span>
                    </div>
                    <div class="small text-muted mb-2"><?= e($item['created_at']) ?></div>
                    <p class="mb-0"><?= e($item['review_text'] ?: 'No written review provided.') ?></p>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($feedback)): ?><div class="text-muted">No consultation feedback yet. Patient and doctor notes will appear here after the visit is completed.</div><?php endif; ?>
    </div>
</div>
