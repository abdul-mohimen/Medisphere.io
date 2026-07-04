<?php
$queryValue = trim((string) ($query ?? ''));
$healthArticles = $healthArticles ?? array_merge($articles ?? [], $news ?? []);
$imageArticles = array_values(array_filter($healthArticles, static fn(array $article): bool => ($article['media_type'] ?? '') === 'image'));
$videoArticles = array_values(array_filter($healthArticles, static fn(array $article): bool => ($article['media_type'] ?? '') === 'video'));
$hasMatches = !empty($healthArticles);
$totalArticleCount = (int) ($totalArticleCount ?? count($healthArticles));
$totalImageCount = (int) ($totalImageCount ?? count($imageArticles));
$totalVideoCount = (int) ($totalVideoCount ?? count($videoArticles));
$subscriptionPageUrl = $subscriptionGate['subscription_page_url'] ?? route_url('payments/subscriptions', ['return_to' => route_url('blog')]);

$careTopics = [
    ['label' => 'Appointments', 'icon' => 'fa-calendar-check', 'text' => 'Booking, schedule flow, and visit preparation.', 'url' => route_url('appointments')],
    ['label' => 'Reports', 'icon' => 'fa-file-medical', 'text' => 'Upload lab reports, prescriptions, and clinical files.', 'url' => route_url('reports')],
    ['label' => 'Find Care', 'icon' => 'fa-stethoscope', 'text' => 'Search doctors, hospitals, fees, and specialties.', 'url' => route_url('find-healthcare')],
    ['label' => 'Privacy', 'icon' => 'fa-shield-heart', 'text' => 'Review consent, privacy, and compliance guidance.', 'url' => route_url('policy', ['slug' => 'privacy-policy'])],
];

$renderArticleCard = static function (array $article) use ($subscriptionPageUrl): void {
    $isVideo = ($article['media_type'] ?? '') === 'video';
    $isLocked = !empty($article['is_locked']);
    $isPremium = !empty($article['is_premium']);
    $image = $isVideo
        ? ($article['video_poster'] ?: $article['card_image'])
        : ($article['card_image'] ?: $article['hero_image']);
    $mediaLabel = $isVideo ? 'Video story' : 'Image story';
    $icon = $isVideo ? 'fa-circle-play' : 'fa-image';
    $targetUrl = route_url('article', ['slug' => $article['slug']]);
    ?>
    <a href="<?= e($targetUrl) ?>" class="blog-story-card blog-dynamic-card <?= $isVideo ? 'is-video' : 'is-image' ?> <?= $isLocked ? 'is-premium-locked' : '' ?>" data-aos="fade-up">
        <div class="blog-card-media">
            <?php if ($isVideo && !empty($article['video_url'])): ?>
                <video autoplay muted loop playsinline preload="metadata" poster="<?= e($image) ?>" aria-label="<?= e($article['title']) ?> video preview">
                    <source src="<?= e(app_url($article['video_url'])) ?>" type="video/mp4">
                </video>
            <?php else: ?>
                <img src="<?= e($image) ?>" alt="<?= e($article['title']) ?>" loading="lazy">
            <?php endif; ?>
            <span class="blog-media-badge"><i class="fa-solid <?= e($icon) ?>"></i> <?= e($mediaLabel) ?></span>
            <?php if ($isPremium): ?>
                <span class="blog-premium-badge"><i class="fa-solid fa-crown"></i> Premium</span>
            <?php endif; ?>
            <?php if ($isVideo && !empty($article['voiceover_url'])): ?>
                <span class="blog-voice-badge"><i class="fa-solid fa-volume-high"></i> Voice</span>
            <?php endif; ?>
            <?php if ($isLocked): ?>
                <div class="blog-lock-overlay"><i class="fa-solid fa-lock"></i><span>Preview available</span></div>
            <?php endif; ?>
        </div>
        <div class="blog-story-body">
            <span class="blog-card-kicker"><?= e(ucfirst((string) $article['type'])) ?> &middot; <?= e($article['category']) ?></span>
            <h3><?= e($article['title']) ?></h3>
            <p><?= e($article['excerpt']) ?></p>
            <div class="blog-card-author">
                <img src="<?= e($article['author_avatar']) ?>" alt="<?= e($article['author_name']) ?>" loading="lazy">
                <span><strong><?= e($article['author_name']) ?></strong><small><?= e($article['author_designation']) ?></small></span>
            </div>
            <div class="blog-story-meta">
                <span><?= e(date('M j, Y', strtotime((string) $article['published_at']))) ?></span>
                <span class="blog-story-read"><?= $isLocked ? 'Unlock' : 'Read' ?> <i class="fa-solid fa-arrow-right"></i></span>
            </div>
        </div>
    </a>
    <?php
};
?>

<section class="blog-hub-hero glass-panel" data-aos="fade-up">
    <div class="blog-hub-copy">
        <span class="eyebrow">Health Blog &amp; News</span>
        <h1>Expert health stories for the MediSphere care journey</h1>
        <p><?= (int) $totalArticleCount ?> original articles and news features across appointments, reports, AI screening, medication safety, privacy, telemedicine, hospital operations, and whole-person care.</p>
        <div class="blog-hub-actions">
            <a href="<?= route_url('find-healthcare') ?>" class="btn btn-primary"><i class="fa-solid fa-stethoscope"></i> Find care</a>
            <a href="<?= route_url('guidelines') ?>" class="btn btn-outline-primary"><i class="fa-solid fa-book-medical"></i> Guidelines</a>
        </div>
    </div>
    <form class="blog-search-panel" method="GET" action="<?= app_url('index.php') ?>">
        <input type="hidden" name="route" value="blog">
        <label for="blogSearchInput">Search the <?= (int) $totalArticleCount ?>-post hub</label>
        <div class="blog-search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input id="blogSearchInput" type="search" name="q" class="form-control" placeholder="Search telemedicine, reports, privacy..." value="<?= e($queryValue) ?>">
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
        <div class="blog-search-suggestions">
            <a href="<?= route_url('blog', ['q' => 'telemedicine']) ?>">Telemedicine</a>
            <a href="<?= route_url('blog', ['q' => 'reports']) ?>">Reports</a>
            <a href="<?= route_url('blog', ['q' => 'privacy']) ?>">Privacy</a>
        </div>
    </form>
</section>

<section class="blog-hub-metrics" data-aos="fade-up">
    <div><strong><?= $totalArticleCount ?></strong><span>Dynamic posts</span></div>
    <div><strong><?= $totalImageCount ?></strong><span>Image articles</span></div>
    <div><strong><?= $totalVideoCount ?></strong><span>Video articles</span></div>
</section>

<?php if ($queryValue !== ''): ?>
    <section class="blog-section-head" data-aos="fade-up">
        <div>
            <span class="eyebrow">Search results</span>
            <h2><?= $hasMatches ? count($healthArticles) . ' matching posts' : 'No matching posts' ?></h2>
        </div>
        <a href="<?= route_url('blog') ?>" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-xmark"></i> Clear search</a>
    </section>
<?php endif; ?>

<?php if (!$hasMatches): ?>
    <section class="blog-empty-state glass-panel" data-aos="fade-up">
        <div class="blog-empty-icon"><i class="fa-solid fa-newspaper"></i></div>
        <div>
            <span class="eyebrow">No exact match</span>
            <h2>No article matched "<?= e($queryValue) ?>".</h2>
            <p class="text-muted mb-0">Try a broader topic such as reports, telemedicine, privacy, AI scanner, or hospital management.</p>
        </div>
        <a href="<?= route_url('blog') ?>" class="btn btn-outline-primary">View all <?= (int) $totalArticleCount ?></a>
    </section>
<?php endif; ?>

<?php if (!empty($imageArticles)): ?>
    <section class="blog-section-head" data-aos="fade-up">
        <div>
            <span class="eyebrow">Image library</span>
            <h2>Image-led health articles</h2>
        </div>
        <span class="blog-count-pill"><i class="fa-solid fa-image"></i> <?= count($imageArticles) ?> posts</span>
    </section>
    <div class="blog-card-grid">
        <?php foreach ($imageArticles as $article): ?>
            <?php $renderArticleCard($article); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($videoArticles)): ?>
    <section class="blog-section-head blog-section-head-spaced" data-aos="fade-up">
        <div>
            <span class="eyebrow">Video library</span>
            <h2>Premium video health features</h2>
        </div>
        <span class="blog-count-pill"><i class="fa-solid fa-circle-play"></i> <?= count($videoArticles) ?> posts</span>
    </section>
    <div class="blog-card-grid">
        <?php foreach ($videoArticles as $article): ?>
            <?php $renderArticleCard($article); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<section class="blog-topic-strip glass-panel" data-aos="fade-up">
    <div>
        <span class="eyebrow">Quick care pathways</span>
        <h2>Turn reading into action</h2>
    </div>
    <div class="blog-topic-grid">
        <?php foreach ($careTopics as $topic): ?>
            <a href="<?= e($topic['url']) ?>" class="blog-topic-link">
                <i class="fa-solid <?= e($topic['icon']) ?>"></i>
                <span><strong><?= e($topic['label']) ?></strong><small><?= e($topic['text']) ?></small></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
