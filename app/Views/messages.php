<section class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <span class="eyebrow">Messages</span>
        <h1 class="mb-0">Care Team Chat</h1>
        <p class="text-muted mb-0 mt-2">Keep clinical follow-ups, care coordination, and visit questions connected to the right contact.</p>
    </div>
    <a href="<?= route_url('consultations') ?>" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-video"></i> Consultations</a>
</section>

<div class="message-layout" data-aos="fade-up">
    <aside class="glass-panel">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <h5 class="mb-0">Contacts</h5>
            <span class="badge text-bg-light"><?= count($contacts) ?></span>
        </div>

        <form class="contact-search-form mb-3">
            <input type="hidden" name="route" value="messages">
            <div class="input-group">
                <input type="search" name="q" class="form-control" value="<?= e($query ?? '') ?>" placeholder="Search contacts">
                <button class="btn btn-primary" aria-label="Search contacts"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </form>

        <div class="contact-list">
            <?php foreach ($contacts as $contact): ?>
                <?php
                $name = $contact['name'] ?? $contact['email'];
                $subtitle = trim(($contact['specialization'] ?? '') ?: (($contact['city'] ?? '') . ' ' . ($contact['country'] ?? '')));
                $unread = (int) ($contact['unread_count'] ?? 0);
                ?>
                <a class="contact-item <?= $contactId == $contact['id'] ? 'active' : '' ?>" href="<?= route_url('messages', ['contact_id' => (int) $contact['id'], 'q' => $query ?? '']) ?>">
                    <div class="avatar-sm"><?= strtoupper(substr($name ?: $contact['email'], 0, 1)) ?></div>
                    <div class="contact-meta flex-grow-1">
                        <div class="fw-semibold contact-name"><?= e($name) ?></div>
                        <div class="small text-muted text-capitalize contact-last"><?= e($contact['user_type']) ?><?= $subtitle ? ' &middot; ' . e($subtitle) : '' ?></div>
                        <?php if (!empty($contact['last_at'])): ?><div class="x-small text-muted"><?= e($contact['last_at']) ?></div><?php endif; ?>
                    </div>
                    <?php if ($unread > 0): ?><span class="contact-unread-badge"><?= $unread ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>

            <?php if (!$contacts): ?>
                <div class="text-muted small">No matching contacts found. Try searching by name, specialty, email, city, or care role.</div>
            <?php endif; ?>
        </div>
    </aside>

    <section class="glass-panel chat-shell" data-contact-id="<?= (int) $contactId ?>">
        <?php if ($activeContact): ?>
            <div class="chat-toolbar d-flex justify-content-between align-items-center gap-3 mb-3">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div class="avatar-sm"><?= strtoupper(substr($activeContact['name'] ?? $activeContact['email'], 0, 1)) ?></div>
                    <div class="min-w-0">
                        <h5 class="mb-0 text-truncate"><?= e($activeContact['name'] ?? $activeContact['email']) ?></h5>
                        <small class="text-muted text-capitalize"><?= e($activeContact['user_type'] ?? 'contact') ?><?= !empty($activeContact['phone']) ? ' &middot; ' . e($activeContact['phone']) : '' ?></small>
                    </div>
                </div>
                <a href="<?= route_url('consultations') ?>" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-video"></i> Start Visit</a>
            </div>
        <?php else: ?>
            <div class="chat-toolbar mb-3">
                <h5 class="mb-0">Select a contact</h5>
            </div>
        <?php endif; ?>

        <div class="chat-messages" id="chatMessages">
            <?php if ($activeContact && $conversation): ?>
                <?php foreach ($conversation as $message): ?>
                    <div class="message-bubble <?= $message['sender_id'] == App\Core\Auth::id() ? 'mine' : 'theirs' ?>">
                        <div><?= nl2br(e($message['message'])) ?></div>
                        <small><?= e($message['timestamp']) ?></small>
                    </div>
                <?php endforeach; ?>
            <?php elseif ($activeContact): ?>
                <div class="chat-empty-state">
                    <div>
                        <i class="fa-regular fa-comments fa-2x mb-2"></i>
                        <div>No messages yet. Start with a brief care update, follow-up question, or appointment note.</div>
                    </div>
                </div>
            <?php else: ?>
                <div class="chat-empty-state">
                    <div>
                        <i class="fa-solid fa-user-group fa-2x mb-2"></i>
                        <div>Choose a contact to view care conversations, visit follow-ups, and coordination notes.</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($activeContact): ?>
            <form id="chatForm" class="d-flex gap-2 mt-3">
                <input type="hidden" name="csrf_token" value="<?= e(App\Core\CSRF::token()) ?>">
                <input type="hidden" name="receiver_id" value="<?= (int) $contactId ?>">
                <input type="text" name="message" class="form-control" placeholder="Type your message..." autocomplete="off" required>
                <button class="btn btn-primary" aria-label="Send message"><i class="fa-solid fa-paper-plane"></i></button>
            </form>
        <?php endif; ?>
    </section>
</div>
