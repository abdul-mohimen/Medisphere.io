<?php
$queryValue = trim((string) ($query ?? ''));
$diseases = array_values($diseases ?? []);
$premiumVideoLocked = !empty($premiumVideoLocked);

$preferredCategories = [
    'High-Consequence Pathogens',
    'Antimicrobial Resistance',
    'Tropical & Vector-Borne',
    'Infectious Diseases',
    'Respiratory',
    'Cardiovascular',
    'Endocrine & Metabolic',
    'Neurology',
    'Oncology',
    'Gastroenterology',
    'Renal & Urology',
    'Mental Health',
    'Musculoskeletal',
    'Maternal & Reproductive',
    'Hematology',
    'Dermatology',
    'Autoimmune & Inflammatory',
    'Critical Care',
    'General Care',
];

$categoryIcon = static fn(string $category): string => match ($category) {
    'High-Consequence Pathogens' => 'fa-virus-covid',
    'Antimicrobial Resistance' => 'fa-shield-virus',
    'Tropical & Vector-Borne' => 'fa-bug',
    'Infectious Diseases' => 'fa-virus',
    'Respiratory' => 'fa-lungs',
    'Cardiovascular' => 'fa-heart-pulse',
    'Endocrine & Metabolic' => 'fa-droplet',
    'Neurology' => 'fa-brain',
    'Oncology' => 'fa-ribbon',
    'Gastroenterology' => 'fa-capsules',
    'Renal & Urology' => 'fa-prescription-bottle-medical',
    'Mental Health' => 'fa-face-smile',
    'Musculoskeletal' => 'fa-bone',
    'Maternal & Reproductive' => 'fa-baby',
    'Hematology' => 'fa-droplet',
    'Dermatology' => 'fa-hand-dots',
    'Autoimmune & Inflammatory' => 'fa-dna',
    'Critical Care' => 'fa-truck-medical',
    default => 'fa-book-medical',
};

$detectedCategories = array_values(array_unique(array_map(static fn(array $disease): string => $disease['library_category'] ?? 'General Care', $diseases)));
$categories = array_values(array_unique(array_merge(['All'], array_filter($preferredCategories, static fn(string $category): bool => in_array($category, $detectedCategories, true)), array_diff($detectedCategories, $preferredCategories))));
$categoryCounts = array_fill_keys($categories, 0);
$lettersWithDiseases = [];
$riskCounts = ['Critical' => 0, 'High' => 0, 'Moderate' => 0, 'Low' => 0];
$diagnosticSet = [];

foreach ($diseases as $disease) {
    $category = $disease['library_category'] ?? 'General Care';
    $categoryCounts['All']++;
    $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
    $letter = $disease['library_letter'] ?? '#';
    $lettersWithDiseases[$letter] = true;
    $risk = $disease['library_risk_level'] ?? 'Low';
    $riskCounts[$risk] = ($riskCounts[$risk] ?? 0) + 1;
    foreach (($disease['library_diagnostics'] ?? []) as $test) {
        $diagnosticSet[(string) $test] = true;
    }
}

$alphabet = range('A', 'Z');
$riskOptions = array_values(array_filter(['Critical', 'High', 'Moderate', 'Low'], static fn(string $risk): bool => ($riskCounts[$risk] ?? 0) > 0));
$diseasePayload = array_map(static fn(array $disease): array => [
    'slug' => $disease['slug'],
    'name' => $disease['disease_name'],
    'summary' => $disease['library_summary'] ?? '',
    'category' => $disease['library_category'] ?? 'General Care',
    'letter' => $disease['library_letter'] ?? '#',
    'image' => $disease['library_image'] ?? '',
    'url' => route_url('disease', ['slug' => $disease['slug']]),
    'care_url' => route_url('find-healthcare'),
    'search' => $disease['library_search'] ?? '',
    'type' => $disease['library_type'] ?? 'Clinical condition',
    'risk_level' => $disease['library_risk_level'] ?? 'Low',
    'icon' => $disease['library_icon'] ?? 'fa-book-medical',
    'diagnostics' => array_values($disease['library_diagnostics'] ?? []),
    'tags' => array_values($disease['library_tags'] ?? []),
], $diseases);

$pathogenCategories = ['High-Consequence Pathogens', 'Antimicrobial Resistance', 'Tropical & Vector-Borne', 'Infectious Diseases'];
$pathogenCount = count(array_filter($diseases, static fn(array $disease): bool => in_array($disease['library_category'] ?? '', $pathogenCategories, true)));
$criticalCount = (int) (($riskCounts['Critical'] ?? 0) + ($riskCounts['High'] ?? 0));
$chronicCount = count(array_filter($diseases, static fn(array $disease): bool => str_contains(strtolower((string) ($disease['library_type'] ?? '')), 'chronic') || in_array('Chronic', $disease['library_tags'] ?? [], true)));
$spotlightCategories = array_slice(array_filter($categories, static fn(string $category): bool => $category !== 'All'), 0, 8);
$priorityDiseases = array_slice(array_values(array_filter($diseases, static fn(array $disease): bool => in_array($disease['library_risk_level'] ?? '', ['Critical', 'High'], true))), 0, 3);
if (!$priorityDiseases) {
    $priorityDiseases = array_slice($diseases, 0, 3);
}

$educationVideos = [
    [
        'title' => 'Lab reports',
        'text' => 'Flags, trends, and clinician-ready notes.',
        'src' => app_url('uploads/media/videos/reading-lab-reports-without-panic-voiced.mp4'),
        'poster' => 'https://images.unsplash.com/photo-1631815588090-d4bfec5b1ccb?auto=format&fit=crop&w=1200&q=85',
        'tier' => 'free',
        'badge' => 'Free',
    ],
    [
        'title' => 'Home metrics',
        'text' => 'Blood pressure, glucose, weight, and follow-up.',
        'src' => app_url('uploads/media/videos/home-metrics-blood-pressure-sugar-weight-voiced.mp4'),
        'poster' => 'https://images.unsplash.com/photo-1532938911079-1b06ac7ceec7?auto=format&fit=crop&w=1200&q=85',
        'tier' => 'premium',
        'badge' => 'Pro',
    ],
    [
        'title' => 'Telemedicine',
        'text' => 'Symptoms, reports, camera setup, and next steps.',
        'src' => app_url('uploads/media/videos/strong-telemedicine-visit-voiced.mp4'),
        'poster' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=1200&q=85',
        'tier' => 'premium',
        'badge' => 'Pro',
    ],
];
?>

<section class="disease-atlas-shell" data-disease-library data-api-url="<?= e(route_url('api/diseases')) ?>">
    <script type="application/json" id="diseaseLibraryData"><?= json_encode($diseasePayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>

    <section class="disease-atlas-hero" data-aos="fade-up">
        <div class="disease-atlas-hero-copy">
            <span class="eyebrow">Global disease atlas</span>
            <h1>Disease Information Library</h1>
            <p>Clinical condition guides spanning high-consequence pathogens, chronic disease, emergency syndromes, diagnostics, prevention, and care pathways.</p>
            <form class="disease-atlas-search" method="GET" action="<?= app_url('index.php') ?>" data-disease-search-form>
                <input type="hidden" name="route" value="diseases">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" name="q" class="form-control" placeholder="Search Ebola, diabetes, MRSA, malaria, COPD..." value="<?= e($queryValue) ?>" autocomplete="off" data-disease-search>
                <button class="btn btn-primary" type="submit">Search</button>
            </form>
        </div>

        <aside class="disease-atlas-hero-panel">
            <img src="https://images.unsplash.com/photo-1579154204601-01588f351e67?auto=format&fit=crop&w=1200&q=88" alt="Modern medical diagnostics laboratory" loading="eager" fetchpriority="high">
            <div class="disease-priority-stack">
                <?php foreach ($priorityDiseases as $item): ?>
                    <a href="<?= route_url('disease', ['slug' => $item['slug']]) ?>">
                        <i class="fa-solid <?= e($item['library_icon'] ?? 'fa-book-medical') ?>"></i>
                        <span><?= e($item['disease_name']) ?></span>
                        <strong><?= e($item['library_risk_level'] ?? 'Low') ?></strong>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>
    </section>

    <section class="disease-atlas-metrics" data-aos="fade-up">
        <article><span data-disease-result-count><?= count($diseases) ?></span><p>published guides</p></article>
        <article><span><?= max(1, count($categories) - 1) ?></span><p>clinical categories</p></article>
        <article><span><?= (int) $pathogenCount ?></span><p>pathogen profiles</p></article>
        <article><span><?= (int) $criticalCount ?></span><p>urgent pathways</p></article>
        <article><span><?= count($diagnosticSet) ?></span><p>diagnostic cues</p></article>
    </section>

    <section class="disease-atlas-workspace" data-aos="fade-up">
        <aside class="disease-atlas-panel">
            <div class="disease-atlas-panel-head">
                <span class="eyebrow">Care access</span>
                <h2>Global care routing</h2>
            </div>
            <div class="disease-care-actions">
                <a href="<?= route_url('find-healthcare') ?>"><i class="fa-solid fa-hospital"></i><span>Top Hospitals</span></a>
                <a href="<?= route_url('find-healthcare') ?>"><i class="fa-solid fa-user-doctor"></i><span>Specialists</span></a>
                <a href="<?= route_url('map', ['nearby' => 1, 'focus' => 'emergency']) ?>#healthcareExplorerMap"><i class="fa-solid fa-truck-medical"></i><span>Emergency Map</span></a>
            </div>
            <div class="disease-risk-summary">
                <span><strong><?= (int) $chronicCount ?></strong> chronic pathways</span>
                <span><strong><?= (int) $criticalCount ?></strong> urgent profiles</span>
            </div>
        </aside>

        <div class="disease-atlas-controls">
            <div class="disease-control-head">
                <div>
                    <span class="eyebrow">Library controls</span>
                    <h2>Browse the condition index</h2>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" data-disease-clear><i class="fa-solid fa-rotate-left"></i> Reset</button>
            </div>

            <div class="disease-filter-block">
                <span>Categories</span>
                <div class="disease-category-tabs" role="tablist" aria-label="Disease categories">
                    <?php foreach ($spotlightCategories as $category): ?>
                        <button type="button" data-disease-category="<?= e($category) ?>" aria-pressed="false">
                            <i class="fa-solid <?= e($categoryIcon($category)) ?>"></i>
                            <span><?= e($category) ?></span>
                            <strong data-disease-category-count="<?= e($category) ?>"><?= (int) ($categoryCounts[$category] ?? 0) ?></strong>
                        </button>
                    <?php endforeach; ?>
                    <button type="button" class="active" data-disease-category="All" aria-pressed="true">
                        <i class="fa-solid fa-layer-group"></i>
                        <span>All</span>
                        <strong data-disease-category-count="All"><?= (int) ($categoryCounts['All'] ?? 0) ?></strong>
                    </button>
                </div>
            </div>

            <div class="disease-filter-row">
                <div class="disease-filter-block">
                    <span>Risk</span>
                    <div class="disease-risk-tabs" aria-label="Risk levels">
                        <button type="button" class="active" data-disease-risk="all" aria-pressed="true">All</button>
                        <?php foreach ($riskOptions as $risk): ?>
                            <button type="button" data-disease-risk="<?= e($risk) ?>" aria-pressed="false"><?= e($risk) ?> <strong><?= (int) ($riskCounts[$risk] ?? 0) ?></strong></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="disease-filter-block">
                    <span>A-Z</span>
                    <div class="disease-az-index" aria-label="A to Z disease index">
                        <button type="button" class="active" data-disease-letter="all" aria-pressed="true">All</button>
                        <?php foreach ($alphabet as $letter): ?>
                            <button type="button" <?= empty($lettersWithDiseases[$letter]) ? 'disabled' : '' ?> data-disease-letter="<?= e($letter) ?>" aria-pressed="false"><?= e($letter) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="disease-results-header" data-aos="fade-up">
        <div>
            <span class="eyebrow">Condition guides</span>
            <h2 data-disease-results-heading><?= $queryValue !== '' ? 'Guides matching "' . e($queryValue) . '"' : 'Global disease and pathogen index' ?></h2>
        </div>
        <div class="disease-filter-status" data-disease-filter-status>Showing all specialties</div>
    </section>

    <div class="disease-atlas-grid disease-results-grid" data-disease-results>
        <?php foreach ($diseases as $disease): ?>
            <a href="<?= route_url('disease', ['slug' => $disease['slug']]) ?>" class="disease-atlas-card" data-aos="fade-up" data-disease-card data-category="<?= e($disease['library_category'] ?? 'General Care') ?>" data-letter="<?= e($disease['library_letter'] ?? '#') ?>" data-risk="<?= e($disease['library_risk_level'] ?? 'Low') ?>" data-search="<?= e($disease['library_search'] ?? '') ?>" aria-label="Open <?= e($disease['disease_name']) ?>">
                <div class="disease-card-media">
                    <img src="<?= e($disease['library_image'] ?? '') ?>" alt="<?= e($disease['disease_name']) ?> clinical visual" loading="lazy">
                    <span class="risk-<?= e(strtolower((string) ($disease['library_risk_level'] ?? 'low'))) ?>"><?= e($disease['library_risk_level'] ?? 'Low') ?></span>
                </div>
                <div class="disease-card-body">
                    <small><i class="fa-solid <?= e($disease['library_icon'] ?? 'fa-book-medical') ?>"></i><?= e($disease['library_category'] ?? 'Guide') ?></small>
                    <h3><?= e($disease['disease_name']) ?></h3>
                    <p><?= e($disease['library_summary'] ?? '') ?></p>
                    <div class="disease-card-footer">
                        <span><?= e($disease['library_type'] ?? 'Clinical condition') ?></span>
                        <strong>Details <i class="fa-solid fa-arrow-right"></i></strong>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="education-empty-state glass-panel <?= empty($diseases) ? '' : 'd-none' ?>" data-disease-empty>
        <i class="fa-solid fa-magnifying-glass"></i>
        <div>
            <h2>No disease information found.</h2>
            <p class="text-muted mb-0">Try a broader term such as fever, diabetes, heart, skin, breathing, or pain.</p>
        </div>
    </div>

    <section class="disease-learning-strip" data-aos="fade-up">
        <?php foreach ($educationVideos as $video): ?>
            <?php $isPremium = ($video['tier'] ?? 'free') === 'premium'; $isLocked = $isPremium && $premiumVideoLocked; ?>
            <article class="<?= $isLocked ? 'is-locked' : '' ?>" <?= $isLocked ? 'data-premium-video-lock role="button" tabindex="0"' : '' ?>>
                <div class="disease-learning-media" data-education-video-player>
                    <?php if ($isLocked): ?>
                        <img src="<?= e($video['poster']) ?>" alt="<?= e($video['title']) ?> locked video thumbnail" loading="lazy">
                        <span class="disease-learning-lock"><i class="fa-solid fa-lock"></i></span>
                    <?php else: ?>
                        <video preload="metadata" poster="<?= e($video['poster']) ?>" data-education-video>
                            <source src="<?= e($video['src']) ?>" type="video/mp4">
                            Your browser does not support video playback.
                        </video>
                        <button type="button" class="education-video-play" data-education-video-toggle aria-label="Play <?= e($video['title']) ?>">
                            <i class="fa-solid fa-play"></i>
                        </button>
                        <div class="education-video-progress"><span data-education-video-progress></span></div>
                    <?php endif; ?>
                </div>
                <div>
                    <span><i class="fa-solid <?= $isPremium ? 'fa-crown' : 'fa-circle-play' ?>"></i><?= e($video['badge']) ?></span>
                    <h3><?= e($video['title']) ?></h3>
                    <p><?= e($video['text']) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="disease-safety-callout" data-aos="fade-up">
        <div>
            <span class="eyebrow">Safety note</span>
            <h2>Urgent symptoms need urgent care</h2>
            <p>Severe chest pain, breathing difficulty, stroke-like weakness, major trauma, uncontrolled bleeding, fainting, or confusion should be treated as urgent or emergency symptoms.</p>
        </div>
        <a href="<?= route_url('map', ['nearby' => 1, 'focus' => 'emergency']) ?>#healthcareExplorerMap" class="btn btn-primary"><i class="fa-solid fa-truck-medical"></i> Emergency Map</a>
    </section>
</section>

<div class="modal fade disease-premium-video-modal" id="diseasePremiumVideoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <span class="eyebrow">Pro video required</span>
                    <h5 class="modal-title">Unlock premium instructional videos</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="disease-premium-modal-icon"><i class="fa-solid fa-crown"></i></div>
                <p>Pro unlocks advanced clinical insights, premium video lessons, detailed guidelines, and the full premium newsletter setup.</p>
                <div class="disease-premium-modal-list">
                    <span><i class="fa-solid fa-check"></i> Premium instructional videos</span>
                    <span><i class="fa-solid fa-check"></i> Detailed health guidelines</span>
                    <span><i class="fa-solid fa-check"></i> Full premium newsletter</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Not now</button>
                <a href="<?= route_url('payments/subscriptions', ['return_to' => route_url('diseases')]) ?>" class="btn btn-primary"><i class="fa-solid fa-arrow-up-right-from-square"></i> Upgrade to Pro</a>
            </div>
        </div>
    </div>
</div>
