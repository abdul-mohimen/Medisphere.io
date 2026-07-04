<section class="mb-4">
    <span class="eyebrow text-capitalize"><?= e(str_replace('_', ' ', $policy['category'])) ?></span>
    <h1><?= e($policy['title']) ?></h1>
    <div class="small text-muted">Version <?= e($policy['version_label']) ?> · Effective <?= e($policy['effective_date']) ?></div>
</section>

<div class="glass-panel article-body-panel" data-aos="fade-up">
    <?= $policy['content'] ?>
</div>
