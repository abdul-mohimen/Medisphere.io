<?php
$helpWorkflows = [
    [
        'title' => 'Patient quick start',
        'icon' => 'fa-user-shield',
        'text' => 'Create a patient account, complete your health profile, add allergies, medications, emergency contacts, and upload recent reports before booking care.',
        'url' => route_url('register'),
        'action' => 'Create account',
    ],
    [
        'title' => 'Find doctors and hospitals',
        'icon' => 'fa-stethoscope',
        'text' => 'Search verified doctors, compare specialties and fees, inspect hospitals, and use map-based discovery for nearby care options.',
        'url' => route_url('find-healthcare'),
        'action' => 'Find care',
    ],
    [
        'title' => 'Book and manage appointments',
        'icon' => 'fa-calendar-check',
        'text' => 'Patients can request appointments with symptoms and timing preferences. Doctors can confirm, reject, complete, or review visit details.',
        'url' => route_url('appointments'),
        'action' => 'Appointments',
    ],
    [
        'title' => 'Upload reports and prescriptions',
        'icon' => 'fa-file-medical',
        'text' => 'Store lab reports, scans, prescriptions, discharge summaries, and clinical documents so your doctor can review them during visits.',
        'url' => route_url('reports'),
        'action' => 'Reports',
    ],
    [
        'title' => 'Use AI screening safely',
        'icon' => 'fa-microscope',
        'text' => 'Use the AI scanner as an educational first step. Save the result for discussion, but never treat AI output as a final diagnosis.',
        'url' => route_url('scanner'),
        'action' => 'AI scanner',
    ],
    [
        'title' => 'Join video consultations',
        'icon' => 'fa-video',
        'text' => 'Prepare your symptom timeline, medicines, and reports before joining a telemedicine session. Escalate urgent symptoms to emergency care.',
        'url' => route_url('consultations'),
        'action' => 'Consultations',
    ],
    [
        'title' => 'Protect record access',
        'icon' => 'fa-user-lock',
        'text' => 'Patients can manage consent for sensitive health records. Shared access should be purposeful, limited, and auditable.',
        'url' => route_url('record-consents'),
        'action' => 'Record consent',
    ],
    [
        'title' => 'Payments and invoices',
        'icon' => 'fa-credit-card',
        'text' => 'Review invoices, payment status, and refund requests from the payments area. Gateways can be configured for production use.',
        'url' => route_url('payments'),
        'action' => 'Payments',
    ],
    [
        'title' => 'Hospital operations',
        'icon' => 'fa-hospital',
        'text' => 'Hospital accounts can manage departments, beds, inventory, doctor assignment requests, and patient record workflows.',
        'url' => route_url('hospital-management'),
        'action' => 'Hospital tools',
    ],
];

$safetyNotes = [
    'Chest pain, breathing difficulty, stroke-like weakness, heavy bleeding, severe allergic reaction, or loss of consciousness require local emergency services immediately.',
    'AI scanner results, article content, and chatbot answers are educational and must not replace a qualified clinician.',
    'Use report uploads, health profile details, and consent tools to give clinicians accurate context before care decisions.',
];

$cmsGuidelines = $guidelines ?? [];
?>

<section class="help-hero glass-panel" data-aos="fade-up">
    <div>
        <span class="eyebrow">MediSphere Help Center</span>
        <h1>Guides for every care workflow</h1>
        <p>Project-specific help for patients, doctors, hospitals, and admins using appointments, records, video consultations, AI screening, payments, compliance, and hospital operations.</p>
    </div>
    <div class="help-hero-actions">
        <a href="<?= route_url('faq') ?>" class="btn btn-primary"><i class="fa-solid fa-circle-question"></i> View FAQ</a>
        <a href="<?= route_url('blog') ?>" class="btn btn-outline-primary"><i class="fa-solid fa-newspaper"></i> Health articles</a>
    </div>
</section>

<section class="help-grid" data-aos="fade-up">
    <?php foreach ($helpWorkflows as $item): ?>
        <a href="<?= e($item['url']) ?>" class="help-card">
            <span class="help-card-icon"><i class="fa-solid <?= e($item['icon']) ?>"></i></span>
            <span class="help-card-copy">
                <strong><?= e($item['title']) ?></strong>
                <small><?= e($item['text']) ?></small>
            </span>
            <span class="help-card-action"><?= e($item['action']) ?> <i class="fa-solid fa-arrow-right"></i></span>
        </a>
    <?php endforeach; ?>
</section>

<section class="help-safety-panel glass-panel" data-aos="fade-up">
    <div>
        <span class="eyebrow">Safety first</span>
        <h2>How MediSphere should be used</h2>
    </div>
    <div class="help-safety-list">
        <?php foreach ($safetyNotes as $note): ?>
            <div><i class="fa-solid fa-shield-heart"></i><span><?= e($note) ?></span></div>
        <?php endforeach; ?>
    </div>
</section>

<?php if (!empty($cmsGuidelines) || !empty($protocols)): ?>
    <section class="help-managed-section" data-aos="fade-up">
        <div class="blog-section-head">
            <div>
                <span class="eyebrow">Admin-managed content</span>
                <h2>Published platform guidance</h2>
            </div>
        </div>
        <div class="row g-4">
            <?php foreach ($cmsGuidelines as $item): ?>
                <div class="col-md-6">
                    <div class="glass-panel h-100 help-managed-card">
                        <h5><?= e($item['title']) ?></h5>
                        <p class="text-muted mb-0"><?= e($item['excerpt'] ?? substr(strip_tags((string) ($item['content'] ?? '')), 0, 160)) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php foreach (($protocols ?? []) as $protocol): ?>
                <div class="col-md-6">
                    <div class="glass-panel h-100 help-managed-card">
                        <span class="blog-card-kicker">Emergency protocol</span>
                        <h5><?= e($protocol['title']) ?></h5>
                        <p class="text-muted mb-0"><?= e($protocol['excerpt'] ?: substr(strip_tags((string) $protocol['content']), 0, 160)) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
