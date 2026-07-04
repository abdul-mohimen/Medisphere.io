<section class="mb-4 text-center">
    <span class="eyebrow">Public policies</span>
    <h1>Privacy, Terms & Compliance</h1>
    <p class="text-muted">Read public policy documents, privacy notices, and compliance statements.</p>
</section>

<div class="row g-4">
    <?php foreach (($policies ?? []) as $policy): ?>
        <div class="col-md-6 col-xl-4" data-aos="fade-up">
            <div class="glass-panel h-100">
                <span class="badge text-bg-light text-capitalize mb-3"><?= e(str_replace('_', ' ', $policy['category'])) ?></span>
                <h4><?= e($policy['title']) ?></h4>
                <div class="small text-muted mb-3">Version <?= e($policy['version_label']) ?> · Effective <?= e($policy['effective_date']) ?></div>
                <a href="<?= route_url('policy', ['slug' => $policy['slug']]) ?>" class="btn btn-outline-primary btn-sm">Open Policy</a>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($policies)): ?><div class="col-12 text-muted">No public policy documents available yet. Published privacy, terms, security, and compliance documents will appear here.</div><?php endif; ?>
</div>
