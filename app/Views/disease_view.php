<?php
$slug = (string) ($disease['slug'] ?? '');
$safeId = preg_replace('/[^a-z0-9_-]+/i', '-', $slug) ?: 'disease';
$category = $disease['library_category'] ?? 'General Care';
$riskLevel = $disease['library_risk_level'] ?? 'Low';
$riskClass = strtolower(preg_replace('/[^a-z0-9]+/i', '-', (string) $riskLevel) ?? 'low');
$diagnostics = array_values($disease['library_diagnostics'] ?? []);
$tags = array_values($disease['library_tags'] ?? []);
$summary = $disease['library_summary'] ?? 'Structured educational information from the disease knowledge base.';

$sectionFallback = static function (string $label): string {
    return '<p class="text-muted mb-0">No ' . e(strtolower($label)) . ' guidance has been added yet.</p>';
};

$listItems = static function (?string $html, int $limit = 6): array {
    $source = (string) $html;
    $items = [];
    if (preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $source, $matches)) {
        $items = array_map(static fn(string $item): string => trim(preg_replace('/\s+/', ' ', strip_tags($item)) ?? ''), $matches[1]);
    }

    if (!$items) {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($source)) ?? '');
        if ($text !== '') {
            $items = preg_split('/(?<=[.!?])\s+/', $text) ?: [];
        }
    }

    return array_slice(array_values(array_filter($items)), 0, $limit);
};

$storageImage = static function (string $slot) use ($slug): ?string {
    if ($slug === '') {
        return null;
    }

    $publicRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
    foreach (['webp', 'jpg', 'jpeg', 'png'] as $extension) {
        $candidates = ["uploads/diseases/{$slug}-{$slot}.{$extension}"];
        if ($slot === 'hero') {
            $candidates[] = "uploads/diseases/{$slug}.{$extension}";
        }

        foreach ($candidates as $candidate) {
            $localPath = $publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
            if (is_file($localPath)) {
                return app_url($candidate);
            }
        }
    }

    return null;
};

$defaultImages = [
    'hero' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=1800&q=90',
    'overview' => 'https://images.unsplash.com/photo-1584982751601-97dcc096659c?auto=format&fit=crop&w=1200&q=86',
    'symptoms' => 'https://images.unsplash.com/photo-1584982751601-97dcc096659c?auto=format&fit=crop&w=1200&q=86',
    'causes' => 'https://images.unsplash.com/photo-1581093458791-9f3c3900df7b?auto=format&fit=crop&w=1200&q=86',
    'prevention' => 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?auto=format&fit=crop&w=1200&q=86',
    'treatment' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=1200&q=86',
    'testing' => 'https://images.unsplash.com/photo-1579154204601-01588f351e67?auto=format&fit=crop&w=1200&q=86',
];

$categoryImages = [
    'High-Consequence Pathogens' => ['hero' => 'https://images.unsplash.com/photo-1581093458791-9f3c3900df7b?auto=format&fit=crop&w=1800&q=90', 'prevention' => 'https://images.unsplash.com/photo-1584515933487-779824d29309?auto=format&fit=crop&w=1200&q=86'],
    'Infectious Diseases' => ['hero' => 'https://images.unsplash.com/photo-1581093458791-9f3c3900df7b?auto=format&fit=crop&w=1800&q=90', 'prevention' => 'https://images.unsplash.com/photo-1584515933487-779824d29309?auto=format&fit=crop&w=1200&q=86'],
    'Respiratory' => ['hero' => 'https://images.unsplash.com/photo-1581594693702-fbdc51b2763b?auto=format&fit=crop&w=1800&q=90'],
    'Cardiovascular' => ['hero' => 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?auto=format&fit=crop&w=1800&q=90'],
    'Neurology' => ['hero' => 'https://images.unsplash.com/photo-1559757175-5700dde675bc?auto=format&fit=crop&w=1800&q=90'],
    'Oncology' => ['hero' => 'https://images.unsplash.com/photo-1579154341098-e4e158cc7f55?auto=format&fit=crop&w=1800&q=90'],
    'Endocrine & Metabolic' => ['hero' => 'https://images.unsplash.com/photo-1579154204601-01588f351e67?auto=format&fit=crop&w=1800&q=90'],
    'Dermatology' => ['hero' => 'https://images.unsplash.com/photo-1582719471384-894fbb16e074?auto=format&fit=crop&w=1800&q=90'],
    'Maternal & Reproductive' => ['hero' => 'https://images.unsplash.com/photo-1559757175-0eb30cd8c063?auto=format&fit=crop&w=1800&q=90'],
];

$imageFor = static function (string $slot) use ($storageImage, $disease, $category, $categoryImages, $defaultImages): string {
    if ($slot === 'hero' && !empty($disease['featured_image_path'])) {
        return app_url((string) $disease['featured_image_path']);
    }

    return $storageImage($slot)
        ?? $categoryImages[$category][$slot]
        ?? $categoryImages[$category]['hero']
        ?? $defaultImages[$slot]
        ?? $defaultImages['hero'];
};

$tabs = [
    ['id' => 'overview', 'label' => 'Overview', 'icon' => 'fa-circle-info', 'title' => 'Clinical overview', 'image' => $imageFor('overview'), 'content' => $disease['overview'] ?: $sectionFallback('Overview'), 'items' => array_slice($tags, 0, 5)],
    ['id' => 'symptoms', 'label' => 'Symptoms', 'icon' => 'fa-stethoscope', 'title' => 'Symptoms and warning signs', 'image' => $imageFor('symptoms'), 'content' => $disease['symptoms'] ?: $sectionFallback('Symptoms'), 'items' => $listItems($disease['symptoms'] ?? '')],
    ['id' => 'causes', 'label' => 'Causes', 'icon' => 'fa-dna', 'title' => 'Causes and risk drivers', 'image' => $imageFor('causes'), 'content' => $disease['causes'] ?: $sectionFallback('Causes'), 'items' => $listItems($disease['causes'] ?? '')],
    ['id' => 'prevention', 'label' => 'Prevention', 'icon' => 'fa-shield-heart', 'title' => 'Prevention and control', 'image' => $imageFor('prevention'), 'content' => $disease['prevention'] ?: $sectionFallback('Prevention'), 'items' => $listItems($disease['prevention'] ?? '')],
    ['id' => 'treatment', 'label' => 'Treatment', 'icon' => 'fa-kit-medical', 'title' => 'Care and treatment notes', 'image' => $imageFor('treatment'), 'content' => $disease['treatment'] ?: $sectionFallback('Treatment'), 'items' => $listItems($disease['treatment'] ?? '')],
    ['id' => 'testing', 'label' => 'Testing', 'icon' => 'fa-vial', 'title' => 'Diagnostic workflow', 'image' => $imageFor('testing'), 'content' => '<p>Testing decisions depend on symptoms, risk level, exposure history, exam findings, and local clinical guidance.</p>', 'items' => $diagnostics ?: ['Clinical history', 'Physical exam', 'Targeted labs']],
];

$clinicalMetrics = [
    ['label' => 'Symptom signals', 'value' => count($listItems($disease['symptoms'] ?? '', 8)), 'icon' => 'fa-stethoscope'],
    ['label' => 'Diagnostic cues', 'value' => count($diagnostics ?: ['Clinical history']), 'icon' => 'fa-vial'],
    ['label' => 'Prevention steps', 'value' => count($listItems($disease['prevention'] ?? '', 8)), 'icon' => 'fa-shield-heart'],
];

$careSteps = [
    ['label' => 'Prepare', 'icon' => 'fa-clipboard-list', 'text' => 'Bring symptom timing, medications, allergies, reports, and relevant exposures.'],
    ['label' => 'Assess', 'icon' => 'fa-user-doctor', 'text' => 'Connect symptoms, history, exam findings, and risk factors.'],
    ['label' => 'Test', 'icon' => 'fa-vial-circle-check', 'text' => 'Use targeted diagnostics when results affect decisions or safety.'],
    ['label' => 'Plan', 'icon' => 'fa-route', 'text' => 'Confirm treatment, prevention, warning signs, and follow-up timing.'],
];
?>

<section class="disease-profile-shell" data-disease-detail>
    <section class="disease-profile-hero" data-aos="fade-up">
        <img src="<?= e($imageFor('hero')) ?>" alt="<?= e($disease['disease_name']) ?> medical visual" loading="eager" fetchpriority="high">
        <div class="disease-profile-hero-overlay"></div>
        <div class="disease-profile-hero-content">
            <a href="<?= route_url('diseases') ?>" class="disease-profile-back"><i class="fa-solid fa-arrow-left"></i> Disease Library</a>
            <div class="disease-profile-title-row">
                <div>
                    <span class="eyebrow"><?= e($category) ?></span>
                    <h1><?= e($disease['disease_name']) ?></h1>
                    <p><?= e($summary) ?></p>
                </div>
                <div class="disease-profile-risk-card risk-<?= e($riskClass) ?>">
                    <span>Risk level</span>
                    <strong><?= e($riskLevel) ?></strong>
                    <small><?= e($disease['library_type'] ?? 'Clinical condition') ?></small>
                </div>
            </div>
            <div class="disease-profile-meta">
                <span><i class="fa-solid <?= e($disease['library_icon'] ?? 'fa-book-medical') ?>"></i><?= e($disease['library_type'] ?? 'Clinical condition') ?></span>
                <span><i class="fa-solid fa-layer-group"></i><?= e($category) ?></span>
                <?php foreach (array_slice($tags, 0, 2) as $tag): ?>
                    <span><i class="fa-solid fa-circle-info"></i><?= e($tag) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="disease-profile-board" aria-label="Disease clinical details">
        <section class="disease-profile-quickbar" data-aos="fade-up">
            <div class="disease-profile-tabs" id="<?= e($safeId) ?>-tabs" role="tablist" aria-label="Disease sections">
                <?php foreach ($tabs as $index => $tab): ?>
                    <button class="<?= $index === 0 ? 'active' : '' ?>" id="<?= e($safeId . '-' . $tab['id']) ?>-tab" data-bs-toggle="pill" data-bs-target="#<?= e($safeId . '-' . $tab['id']) ?>" type="button" role="tab" aria-controls="<?= e($safeId . '-' . $tab['id']) ?>" aria-selected="<?= $index === 0 ? 'true' : 'false' ?>">
                        <i class="fa-solid <?= e($tab['icon']) ?>"></i>
                        <span><?= e($tab['label']) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="disease-profile-actions">
                <a href="<?= route_url('find-healthcare') ?>" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-user-doctor"></i> Find Specialist</a>
                <a href="<?= route_url('map', ['nearby' => 1, 'focus' => 'emergency']) ?>#healthcareExplorerMap" class="btn btn-primary btn-sm"><i class="fa-solid fa-truck-medical"></i> Emergency Map</a>
            </div>
        </section>

        <section class="disease-profile-metrics" aria-label="Disease summary metrics" data-aos="fade-up">
            <?php foreach ($clinicalMetrics as $metric): ?>
                <article>
                    <i class="fa-solid <?= e($metric['icon']) ?>"></i>
                    <strong><?= (int) $metric['value'] ?></strong>
                    <span><?= e($metric['label']) ?></span>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="disease-profile-lower-grid">
            <section class="disease-profile-section-panel" data-aos="fade-up">
                <div class="tab-content">
                    <?php foreach ($tabs as $index => $tab): ?>
                        <article class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>" id="<?= e($safeId . '-' . $tab['id']) ?>" role="tabpanel" aria-labelledby="<?= e($safeId . '-' . $tab['id']) ?>-tab" tabindex="0">
                            <div class="disease-profile-section-head">
                                <div>
                                    <span class="eyebrow"><?= e($tab['label']) ?></span>
                                    <h2><?= e($tab['title']) ?></h2>
                                </div>
                                <div class="disease-profile-section-icon"><i class="fa-solid <?= e($tab['icon']) ?>"></i></div>
                            </div>
                            <div class="disease-profile-section-body">
                                <figure>
                                    <img src="<?= e($tab['image']) ?>" alt="<?= e($tab['label']) ?> medical visual" loading="lazy">
                                </figure>
                                <div class="disease-profile-section-copy">
                                    <div class="disease-rich-text"><?= $tab['content'] ?></div>
                                    <?php if (!empty($tab['items'])): ?>
                                        <div class="disease-profile-chip-row">
                                            <?php foreach ($tab['items'] as $item): ?>
                                                <span><i class="fa-solid <?= $tab['id'] === 'testing' ? 'fa-vial' : 'fa-check' ?>"></i><?= e($item) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="disease-profile-support-grid" data-aos="fade-up">
                <article class="disease-profile-side-card">
                    <div class="disease-profile-side-head">
                        <span class="eyebrow">Diagnostics</span>
                        <i class="fa-solid fa-vial"></i>
                    </div>
                    <h2>Likely evaluation points</h2>
                    <div class="disease-profile-chip-list">
                        <?php foreach (($diagnostics ?: ['Clinical history', 'Physical exam', 'Targeted labs']) as $test): ?>
                            <span><i class="fa-solid fa-vial"></i><?= e($test) ?></span>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="disease-profile-side-card urgent">
                    <div class="disease-profile-side-head">
                        <span class="eyebrow">Escalation</span>
                        <i class="fa-solid fa-truck-medical"></i>
                    </div>
                    <h2>When care should be urgent</h2>
                    <div class="disease-rich-text"><?= $disease['emergency_notes'] ?: '<p class="text-muted mb-0">If symptoms are severe or rapidly worsening, seek urgent medical help.</p>' ?></div>
                    <a href="<?= route_url('map', ['nearby' => 1, 'focus' => 'emergency']) ?>#healthcareExplorerMap" class="btn btn-primary btn-sm mt-auto"><i class="fa-solid fa-truck-medical"></i> Emergency Map</a>
                </article>

                <article class="disease-profile-side-card care">
                    <div class="disease-profile-side-head">
                        <span class="eyebrow">Care network</span>
                        <i class="fa-solid fa-hospital"></i>
                    </div>
                    <h2>Specialists and hospitals</h2>
                    <div class="disease-profile-care-links">
                        <a href="<?= route_url('find-healthcare') ?>"><i class="fa-solid fa-user-doctor"></i> Specialists</a>
                        <a href="<?= route_url('find-healthcare') ?>"><i class="fa-solid fa-hospital"></i> Top hospitals</a>
                    </div>
                </article>
            </section>

            <section class="disease-profile-care-plan" data-aos="fade-up">
                <div class="education-section-head">
                    <div>
                        <span class="eyebrow">Care pathway</span>
                        <h2>From concern to follow-up</h2>
                    </div>
                    <a href="<?= route_url('appointments', ['book' => '1']) ?>" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-calendar-check"></i> Book Care</a>
                </div>
                <div class="disease-profile-timeline">
                    <?php foreach ($careSteps as $step): ?>
                        <article>
                            <i class="fa-solid <?= e($step['icon']) ?>"></i>
                            <strong><?= e($step['label']) ?></strong>
                            <p><?= e($step['text']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </section>
    </section>

    <?php if (!empty($relatedDiseases)): ?>
        <section class="disease-related-section" data-aos="fade-up">
            <div class="education-section-head mb-3">
                <div>
                    <span class="eyebrow">Related conditions</span>
                    <h2>Continue learning</h2>
                </div>
                <a href="<?= route_url('diseases') ?>" class="btn btn-outline-primary btn-sm">Open Library</a>
            </div>
            <div class="disease-atlas-grid">
                <?php foreach ($relatedDiseases as $related): ?>
                    <a href="<?= route_url('disease', ['slug' => $related['slug']]) ?>" class="disease-atlas-card" aria-label="Open <?= e($related['disease_name']) ?>">
                        <div class="disease-card-media">
                            <img src="<?= e($related['library_image'] ?? '') ?>" alt="<?= e($related['disease_name']) ?> clinical visual" loading="lazy">
                            <span class="risk-<?= e(strtolower((string) ($related['library_risk_level'] ?? 'low'))) ?>"><?= e($related['library_risk_level'] ?? 'Low') ?></span>
                        </div>
                        <div class="disease-card-body">
                            <small><i class="fa-solid <?= e($related['library_icon'] ?? 'fa-book-medical') ?>"></i><?= e($related['library_category'] ?? 'Guide') ?></small>
                            <h3><?= e($related['disease_name']) ?></h3>
                            <p><?= e($related['library_summary'] ?? '') ?></p>
                            <div class="disease-card-footer">
                                <span><?= e($related['library_type'] ?? 'Clinical condition') ?></span>
                                <strong>Details <i class="fa-solid fa-arrow-right"></i></strong>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</section>
