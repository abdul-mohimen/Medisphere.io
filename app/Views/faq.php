<?php
$projectFaqGroups = [
    'Patients' => [
        [
            'question' => 'How do I prepare before booking an appointment?',
            'answer' => 'Complete your health profile, add allergies and medicines, upload recent reports, and write a short symptom timeline. This gives the doctor better context before the visit.',
        ],
        [
            'question' => 'Can I share my medical records with a doctor or hospital?',
            'answer' => 'Yes. Use the record consent tools to control which records can be accessed. Access should only be granted when it supports your care.',
        ],
        [
            'question' => 'Where do I upload lab reports, prescriptions, and discharge summaries?',
            'answer' => 'Open Reports to upload PDFs or images. Use clear labels so doctors can find the correct report during appointments or consultations.',
        ],
    ],
    'Doctors' => [
        [
            'question' => 'How do doctors manage appointment requests?',
            'answer' => 'Doctors review appointment details, symptoms, patient context, and timing, then confirm, reject, or complete the appointment from the Appointments area.',
        ],
        [
            'question' => 'Can doctors use video consultations?',
            'answer' => 'Yes. Eligible appointments can move into consultation rooms where doctor and patient can use video, care notes, and follow-up workflows.',
        ],
        [
            'question' => 'How should AI scanner results be used?',
            'answer' => 'AI scanner results should be treated as educational triage support. Doctors should make final clinical decisions using history, examination, reports, and judgment.',
        ],
    ],
    'Hospitals' => [
        [
            'question' => 'What can hospital accounts manage?',
            'answer' => 'Hospital users can manage departments, bed units, inventory, doctor assignment requests, and patient record workflows from Hospital Management.',
        ],
        [
            'question' => 'How does hospital inventory support patient care?',
            'answer' => 'Inventory visibility helps teams monitor low stock and reduce care delays caused by missing medications, supplies, or clinical items.',
        ],
    ],
    'Security and Compliance' => [
        [
            'question' => 'How does MediSphere protect sensitive health information?',
            'answer' => 'The platform includes role-based areas, consent workflows, document verification, audit-oriented records, and compliance center features. Production deployments should use HTTPS and secure secrets.',
        ],
        [
            'question' => 'Are health articles, chatbot answers, and AI scans medical advice?',
            'answer' => 'No. They are educational support tools. Patients should contact qualified clinicians for diagnosis, prescriptions, treatment plans, or urgent concerns.',
        ],
        [
            'question' => 'What symptoms should bypass online booking?',
            'answer' => 'Severe chest pain, breathing difficulty, stroke-like symptoms, heavy bleeding, severe allergic reaction, major trauma, or loss of consciousness require local emergency services immediately.',
        ],
    ],
    'Payments and Admin' => [
        [
            'question' => 'How are payments and invoices handled?',
            'answer' => 'The Payments area supports invoices, payment status, refund requests, and gateway adapters. Production credentials should be configured in the environment configuration.',
        ],
        [
            'question' => 'How can admins manage site content?',
            'answer' => 'Admins can manage articles, FAQs, disease information, settings, media, communication templates, verification requests, and compliance operations from admin sections.',
        ],
    ],
];

$groups = !empty($faqGroups) ? $faqGroups : $projectFaqGroups;
$index = 0;
?>

<section class="help-hero glass-panel" data-aos="fade-up">
    <div>
        <span class="eyebrow">Support Center</span>
        <h1>MediSphere FAQ</h1>
        <p>Answers tailored to this healthcare platform: appointments, reports, AI scanner, video consultations, patient consent, hospital management, payments, and admin workflows.</p>
    </div>
    <div class="help-hero-actions">
        <a href="<?= route_url('guidelines') ?>" class="btn btn-primary"><i class="fa-solid fa-book-medical"></i> Help guides</a>
        <a href="<?= route_url('find-healthcare') ?>" class="btn btn-outline-primary"><i class="fa-solid fa-stethoscope"></i> Find care</a>
    </div>
</section>

<section class="faq-shell glass-panel" data-aos="fade-up">
    <div class="accordion faq-accordion" id="faqAccordion">
        <?php foreach ($groups as $category => $items): ?>
            <div class="faq-category-heading">
                <i class="fa-solid fa-circle-info"></i>
                <span><?= e((string) $category) ?></span>
            </div>
            <?php foreach ($items as $faq): ?>
                <?php
                $question = $faq['question'] ?? $faq['q'] ?? '';
                $answer = $faq['answer'] ?? $faq['a'] ?? '';
                ?>
                <div class="accordion-item faq-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= $index ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $index ?>" aria-expanded="<?= $index ? 'false' : 'true' ?>" aria-controls="faq<?= $index ?>">
                            <?= e($question) ?>
                        </button>
                    </h2>
                    <div id="faq<?= $index ?>" class="accordion-collapse collapse <?= $index ? '' : 'show' ?>" data-bs-parent="#faqAccordion">
                        <div class="accordion-body"><?= e($answer) ?></div>
                    </div>
                </div>
                <?php $index++; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</section>

<section class="faq-action-strip glass-panel" data-aos="fade-up">
    <div>
        <span class="eyebrow">Need a workflow?</span>
        <h2>Jump to the right MediSphere module</h2>
    </div>
    <div class="faq-action-links">
        <a href="<?= route_url('appointments') ?>"><i class="fa-solid fa-calendar-check"></i> Appointments</a>
        <a href="<?= route_url('reports') ?>"><i class="fa-solid fa-file-medical"></i> Reports</a>
        <a href="<?= route_url('scanner') ?>"><i class="fa-solid fa-microscope"></i> AI Scanner</a>
        <a href="<?= route_url('compliance-center') ?>"><i class="fa-solid fa-shield-heart"></i> Compliance</a>
    </div>
</section>
