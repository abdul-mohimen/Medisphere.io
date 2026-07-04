<?php
$settingMap = [];
foreach (($settings ?? []) as $setting) {
    $settingMap[$setting['setting_key']] = $setting['setting_value'];
}
?>
<section class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <span class="eyebrow">Admin CMS</span>
        <h1 class="mb-0">Content Management</h1>
    </div>
    <div class="small text-muted">Manage FAQs, blog/news articles, guidelines, emergency protocols, disease data, media assets, and public site text.</div>
</section>

<div class="ux-stepper" data-stepper>
    <div class="ux-stepper-nav" role="tablist" aria-label="Content management sections">
        <button class="ux-stepper-tab is-active" data-stepper-tab data-stepper-target="content-step-media">
            <span class="ux-stepper-number">1</span>
            <span class="ux-stepper-label"><strong>Media</strong><span>Uploads and assets</span></span>
        </button>
        <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="content-step-knowledge">
            <span class="ux-stepper-number">2</span>
            <span class="ux-stepper-label"><strong>Knowledge</strong><span>FAQ and articles</span></span>
        </button>
        <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="content-step-library">
            <span class="ux-stepper-number">3</span>
            <span class="ux-stepper-label"><strong>Library</strong><span>Diseases and settings</span></span>
        </button>
    </div>
    <div class="ux-stepper-panels">
        <section class="ux-stepper-panel is-active" id="content-step-media" data-stepper-panel>
<div class="row g-4 mb-4">
    <div class="col-xl-4" data-aos="fade-up">
        <div class="glass-panel h-100">
            <h4 class="mb-3">Media Library Upload</h4>
            <form method="POST" action="<?= route_url('admin/content/media-upload') ?>" enctype="multipart/form-data" class="row g-3">
                <?= csrf_field() ?>
                <div class="col-12"><label class="form-label">Media Title</label><input type="text" name="title" class="form-control" placeholder="Homepage banner image"></div>
                <div class="col-12"><label class="form-label">File</label><input type="file" name="media_file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf" required></div>
                <div class="col-12 d-grid"><button class="btn btn-primary">Upload Media</button></div>
            </form>
            <hr>
            <div class="small text-muted">After upload, copy the asset path into the featured image field of an article or disease record.</div>
        </div>
    </div>
    <div class="col-xl-8" data-aos="fade-up">
        <div class="glass-panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Recent Media Assets</h4>
                <span class="small text-muted">Use asset paths in blog, disease, and protocol content</span>
            </div>
            <div class="row g-3">
                <?php foreach (($mediaAssets ?? []) as $asset): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="media-asset-card">
                            <?php if (str_starts_with((string) $asset['mime_type'], 'image/')): ?>
                                <img src="<?= app_url($asset['file_path']) ?>" alt="<?= e($asset['title']) ?>" class="media-asset-thumb">
                            <?php else: ?>
                                <div class="media-asset-thumb pdf-thumb"><i class="fa-solid fa-file-pdf fa-2x text-danger"></i></div>
                            <?php endif; ?>
                            <h6 class="mt-3 mb-1"><?= e($asset['title']) ?></h6>
                            <div class="small text-muted mb-2"><?= e($asset['mime_type']) ?> · <?= round(((int) $asset['file_size']) / 1024, 1) ?> KB</div>
                            <div class="asset-path-box"><?= e($asset['file_path']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($mediaAssets)): ?><div class="col-12 text-muted">No media uploaded yet. Add images, PDFs, and visual assets to support articles, disease guides, protocols, and public pages.</div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

        </section>
        <section class="ux-stepper-panel" id="content-step-knowledge" data-stepper-panel>
<div class="row g-4">
    <div class="col-xl-6" data-aos="fade-up">
        <div class="glass-panel h-100">
            <h4 class="mb-3">FAQ Management</h4>
            <form method="POST" action="<?= route_url('admin/content/faq-save') ?>" class="row g-3 mb-4">
                <?= csrf_field() ?>
                <input type="hidden" name="faq_id" value="">
                <div class="col-md-8"><label class="form-label">Question</label><input type="text" name="question" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Category</label><input type="text" name="category" class="form-control" placeholder="Appointments"></div>
                <div class="col-12"><label class="form-label">Answer</label><textarea name="answer" class="form-control" rows="4" required></textarea></div>
                <div class="col-md-4"><label class="form-label">Sort Order</label><input type="number" name="sort_order" class="form-control" value="0"></div>
                <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="published">Published</option><option value="draft">Draft</option></select></div>
                <div class="col-md-4 d-grid align-items-end"><button class="btn btn-primary">Save FAQ</button></div>
            </form>
            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Question</th><th>Category</th><th>Status</th></tr></thead><tbody><?php foreach (($faqs ?? []) as $faq): ?><tr><td><?= e($faq['question']) ?><div class="small text-muted"><?= e(substr(strip_tags($faq['answer']), 0, 90)) ?>...</div></td><td><?= e($faq['category']) ?></td><td><span class="badge text-bg-light text-capitalize"><?= e($faq['status']) ?></span></td></tr><?php endforeach; ?><?php if (empty($faqs)): ?><tr><td colspan="3" class="text-center text-muted py-4">No FAQs created yet. Add answers for appointments, reports, privacy, payments, and account access to support visitors.</td></tr><?php endif; ?></tbody></table></div>
        </div>
    </div>

    <div class="col-xl-6" data-aos="fade-up">
        <div class="glass-panel h-100">
            <h4 class="mb-3">Article / Guideline / Protocol Management</h4>
            <form method="POST" action="<?= route_url('admin/content/article-save') ?>" class="row g-3 mb-4 cms-form">
                <?= csrf_field() ?>
                <input type="hidden" name="article_id" value="">
                <div class="col-md-8"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Type</label><select name="type" class="form-select"><option value="blog">Blog</option><option value="news">News</option><option value="guideline">Guideline</option><option value="emergency_protocol">Emergency Protocol</option></select></div>
                <div class="col-md-6"><label class="form-label">Slug</label><input type="text" name="slug" class="form-control" placeholder="auto-from-title"></div>
                <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="published">Published</option><option value="draft">Draft</option></select></div>
                <div class="col-md-6"><label class="form-label">Featured Image Path</label><input type="text" name="featured_image_path" class="form-control" placeholder="uploads/media/example.webp"></div>
                <div class="col-md-6"><label class="form-label">Published At</label><input type="datetime-local" name="published_at" class="form-control"></div>
                <div class="col-12"><label class="form-label">Excerpt</label><textarea name="excerpt" class="form-control" rows="2" placeholder="Short preview for cards and listings"></textarea></div>
                <div class="col-md-6"><label class="form-label">SEO Title</label><input type="text" name="seo_title" class="form-control" placeholder="Optional SEO title"></div>
                <div class="col-md-6"><label class="form-label">SEO Description</label><input type="text" name="seo_description" class="form-control" placeholder="Optional SEO description"></div>
                <div class="col-12"><label class="form-label">Rich Content</label><textarea name="content" class="form-control rich-editor-source" rows="10" data-rich-editor required></textarea></div>
                <div class="col-12 d-grid"><button class="btn btn-primary">Save Article</button></div>
            </form>
            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Title</th><th>Type</th><th>Status</th></tr></thead><tbody><?php foreach (($articles ?? []) as $article): ?><tr><td><?= e($article['title']) ?><div class="small text-muted">/<?= e($article['slug']) ?></div></td><td class="text-capitalize"><?= e(str_replace('_', ' ', $article['type'])) ?></td><td><span class="badge text-bg-light text-capitalize"><?= e($article['status']) ?></span></td></tr><?php endforeach; ?><?php if (empty($articles)): ?><tr><td colspan="3" class="text-center text-muted py-4">No articles created yet. Publish health education, guideline updates, and care journey stories for the public content hub.</td></tr><?php endif; ?></tbody></table></div>
        </div>
    </div>
</div>

        </section>
        <section class="ux-stepper-panel" id="content-step-library" data-stepper-panel>
<div class="row g-4 mt-1">
    <div class="col-xl-7" data-aos="fade-up">
        <div class="glass-panel h-100">
            <h4 class="mb-3">Disease Information Database</h4>
            <form method="POST" action="<?= route_url('admin/content/disease-save') ?>" class="row g-3 mb-4 cms-form">
                <?= csrf_field() ?>
                <input type="hidden" name="disease_id" value="">
                <div class="col-md-8"><label class="form-label">Disease Name</label><input type="text" name="disease_name" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Slug</label><input type="text" name="slug" class="form-control" placeholder="auto-from-name"></div>
                <div class="col-md-6"><label class="form-label">Featured Image Path</label><input type="text" name="featured_image_path" class="form-control" placeholder="uploads/media/example.webp"></div>
                <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="published">Published</option><option value="draft">Draft</option></select></div>
                <div class="col-md-6"><label class="form-label">SEO Title</label><input type="text" name="seo_title" class="form-control" placeholder="Optional SEO title"></div>
                <div class="col-md-6"><label class="form-label">SEO Description</label><input type="text" name="seo_description" class="form-control" placeholder="Optional SEO description"></div>
                <div class="col-12"><label class="form-label">Overview</label><textarea name="overview" class="form-control rich-editor-source" rows="4" data-rich-editor></textarea></div>
                <div class="col-md-6"><label class="form-label">Symptoms</label><textarea name="symptoms" class="form-control rich-editor-source" rows="4" data-rich-editor></textarea></div>
                <div class="col-md-6"><label class="form-label">Causes</label><textarea name="causes" class="form-control rich-editor-source" rows="4" data-rich-editor></textarea></div>
                <div class="col-md-6"><label class="form-label">Prevention</label><textarea name="prevention" class="form-control rich-editor-source" rows="4" data-rich-editor></textarea></div>
                <div class="col-md-6"><label class="form-label">Treatment</label><textarea name="treatment" class="form-control rich-editor-source" rows="4" data-rich-editor></textarea></div>
                <div class="col-12"><label class="form-label">Emergency Notes</label><textarea name="emergency_notes" class="form-control rich-editor-source" rows="4" data-rich-editor></textarea></div>
                <div class="col-12 d-grid"><button class="btn btn-primary">Save Disease Info</button></div>
            </form>
            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Disease</th><th>Status</th><th>Updated</th></tr></thead><tbody><?php foreach (($diseases ?? []) as $disease): ?><tr><td><?= e($disease['disease_name']) ?><div class="small text-muted">/<?= e($disease['slug']) ?></div></td><td><span class="badge text-bg-light text-capitalize"><?= e($disease['status']) ?></span></td><td><?= e($disease['updated_at']) ?></td></tr><?php endforeach; ?><?php if (empty($diseases)): ?><tr><td colspan="3" class="text-center text-muted py-4">No disease information added yet. Build condition guides with symptoms, prevention, treatment, and emergency notes.</td></tr><?php endif; ?></tbody></table></div>
        </div>
    </div>

    <div class="col-xl-5" data-aos="fade-up">
        <div class="glass-panel h-100">
            <h4 class="mb-3">Public Site Settings</h4>
            <form method="POST" action="<?= route_url('admin/content/settings-save') ?>" class="row g-3">
                <?= csrf_field() ?>
                <div class="col-12"><label class="form-label">Homepage Notice</label><textarea name="homepage_notice" class="form-control" rows="3" placeholder="Short sitewide announcement"><?= e($settingMap['homepage_notice'] ?? '') ?></textarea></div>
                <div class="col-12"><label class="form-label">Footer Contact</label><textarea name="footer_contact" class="form-control" rows="3" placeholder="Support email, phone, office hours"><?= e($settingMap['footer_contact'] ?? '') ?></textarea></div>
                <div class="col-12"><label class="form-label">Emergency Hotline</label><input type="text" name="emergency_hotline" class="form-control" value="<?= e($settingMap['emergency_hotline'] ?? '') ?>" placeholder="1122 / local emergency contact"></div>
                <div class="col-12"><label class="form-label">About Summary</label><textarea name="about_summary" class="form-control" rows="4" placeholder="Short public about text"><?= e($settingMap['about_summary'] ?? '') ?></textarea></div>
                <div class="col-12 d-grid"><button class="btn btn-primary">Save Settings</button></div>
            </form>
            <hr>
            <div class="small text-muted">Public content will automatically power the blog, FAQ page, guidelines, emergency protocol content, and disease library as you publish items.</div>
        </div>
    </div>
</div>
        </section>
    </div>
</div>
