<section class="mb-4">
    <span class="eyebrow text-capitalize"><?= e($article['type']) ?></span>
    <h1><?= e($article['title']) ?></h1>
    <p class="text-muted"><?= e($article['excerpt'] ?: '') ?></p>
    <div class="article-meta-row small text-muted">Published: <?= e($article['published_at'] ?: $article['created_at']) ?> · <?= (int) ($readingTime ?? 1) ?> min read</div>
</section>

<div class="glass-panel article-body-panel" data-aos="fade-up">
    <?php if (!empty($article['featured_image_path'])): ?><img src="<?= app_url($article['featured_image_path']) ?>" alt="<?= e($article['title']) ?>" class="article-hero-image mb-4"><?php endif; ?>
    <?= $article['content'] ?>
</div>

<?php if (!empty($relatedArticles)): ?>
    <section class="mt-5" data-aos="fade-up">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Related Articles</h3>
            <a href="<?= route_url('blog') ?>" class="btn btn-outline-primary btn-sm">View All Content</a>
        </div>
        <div class="row g-4">
            <?php foreach ($relatedArticles as $related): ?>
                <div class="col-md-4">
                    <div class="glass-panel h-100 article-card-shell">
                        <?php if (!empty($related['featured_image_path'])): ?><img src="<?= app_url($related['featured_image_path']) ?>" alt="<?= e($related['title']) ?>" class="article-card-image mb-3"><?php endif; ?>
                        <span class="badge text-bg-light text-capitalize mb-3"><?= e($related['type']) ?></span>
                        <h5><?= e($related['title']) ?></h5>
                        <p class="text-muted"><?= e($related['excerpt'] ?: substr(strip_tags($related['content']), 0, 110)) ?>...</p>
                        <a href="<?= route_url('article', ['slug' => $related['slug']]) ?>" class="btn btn-outline-primary btn-sm">Read Article</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
