<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\HtmlSanitizer;
use App\Core\Security;
use App\Core\View;
use App\Models\CmsArticle;
use App\Models\CmsFaq;
use App\Models\DiseaseInformation;
use App\Models\MediaAsset;
use App\Models\SiteSetting;

class AdminContentController
{
    public function index(): void
    {
        Auth::requireLogin(['admin']);

        View::render('admin_content', [
            'title' => 'Content Management',
            'faqs' => (new CmsFaq())->all('all'),
            'articles' => (new CmsArticle())->all('all'),
            'diseases' => (new DiseaseInformation())->all('all'),
            'settings' => (new SiteSetting())->all(),
            'mediaAssets' => (new MediaAsset())->recent(18),
        ]);
    }

    public function saveFaq(): void
    {
        Auth::requireLogin(['admin']);
        if (!$this->checkCsrf()) {
            return;
        }

        $question = Security::cleanString($_POST['question'] ?? '');
        $answer = trim((string) ($_POST['answer'] ?? ''));
        if ($question === '' || $answer === '') {
            flash('error', 'Question and answer are required.', 'danger');
            redirect('admin/content');
        }

        (new CmsFaq())->save([
            'id' => Security::cleanInt($_POST['faq_id'] ?? 0),
            'question' => $question,
            'answer' => $answer,
            'category' => Security::cleanString($_POST['category'] ?? 'General'),
            'status' => Security::cleanString($_POST['status'] ?? 'published'),
            'sort_order' => Security::cleanInt($_POST['sort_order'] ?? 0),
        ]);

        flash('success', 'FAQ saved successfully.', 'success');
        redirect('admin/content');
    }

    public function saveArticle(): void
    {
        Auth::requireLogin(['admin']);
        if (!$this->checkCsrf()) {
            return;
        }

        $title = Security::cleanString($_POST['title'] ?? '');
        $content = HtmlSanitizer::clean(trim((string) ($_POST['content'] ?? '')));
        if ($title === '' || $content === '') {
            flash('error', 'Article title and content are required.', 'danger');
            redirect('admin/content');
        }

        $slug = $this->slugify(Security::cleanString($_POST['slug'] ?? '') ?: $title);
        (new CmsArticle())->save([
            'id' => Security::cleanInt($_POST['article_id'] ?? 0),
            'slug' => $slug,
            'title' => $title,
            'excerpt' => Security::cleanString($_POST['excerpt'] ?? ''),
            'content' => $content,
            'type' => Security::cleanString($_POST['type'] ?? 'blog'),
            'status' => Security::cleanString($_POST['status'] ?? 'published'),
            'author_id' => Auth::id(),
            'published_at' => Security::cleanString($_POST['published_at'] ?? ''),
            'featured_image_path' => Security::cleanString($_POST['featured_image_path'] ?? ''),
            'seo_title' => Security::cleanString($_POST['seo_title'] ?? ''),
            'seo_description' => Security::cleanString($_POST['seo_description'] ?? ''),
        ]);

        flash('success', 'Article saved successfully.', 'success');
        redirect('admin/content');
    }

    public function saveDisease(): void
    {
        Auth::requireLogin(['admin']);
        if (!$this->checkCsrf()) {
            return;
        }

        $name = Security::cleanString($_POST['disease_name'] ?? '');
        if ($name === '') {
            flash('error', 'Disease name is required.', 'danger');
            redirect('admin/content');
        }

        $slug = $this->slugify(Security::cleanString($_POST['slug'] ?? '') ?: $name);
        (new DiseaseInformation())->save([
            'id' => Security::cleanInt($_POST['disease_id'] ?? 0),
            'slug' => $slug,
            'disease_name' => $name,
            'overview' => HtmlSanitizer::clean(trim((string) ($_POST['overview'] ?? ''))),
            'symptoms' => HtmlSanitizer::clean(trim((string) ($_POST['symptoms'] ?? ''))),
            'causes' => HtmlSanitizer::clean(trim((string) ($_POST['causes'] ?? ''))),
            'prevention' => HtmlSanitizer::clean(trim((string) ($_POST['prevention'] ?? ''))),
            'treatment' => HtmlSanitizer::clean(trim((string) ($_POST['treatment'] ?? ''))),
            'emergency_notes' => HtmlSanitizer::clean(trim((string) ($_POST['emergency_notes'] ?? ''))),
            'status' => Security::cleanString($_POST['status'] ?? 'published'),
            'featured_image_path' => Security::cleanString($_POST['featured_image_path'] ?? ''),
            'seo_title' => Security::cleanString($_POST['seo_title'] ?? ''),
            'seo_description' => Security::cleanString($_POST['seo_description'] ?? ''),
        ]);

        flash('success', 'Disease information saved successfully.', 'success');
        redirect('admin/content');
    }

    public function saveSettings(): void
    {
        Auth::requireLogin(['admin']);
        if (!$this->checkCsrf()) {
            return;
        }

        $settingMap = [
            'homepage_notice' => 'content',
            'footer_contact' => 'content',
            'emergency_hotline' => 'content',
            'about_summary' => 'content',
        ];

        $settings = new SiteSetting();
        foreach ($settingMap as $key => $group) {
            $value = trim((string) ($_POST[$key] ?? ''));
            $settings->save($key, $value, $group);
        }

        flash('success', 'Site settings updated.', 'success');
        redirect('admin/content');
    }

    public function uploadMedia(): void
    {
        Auth::requireLogin(['admin']);
        if (!$this->checkCsrf()) {
            return;
        }

        if (empty($_FILES['media_file']['name'])) {
            flash('error', 'Please choose a media file to upload.', 'danger');
            redirect('admin/content');
        }

        $file = $_FILES['media_file'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed, true)) {
            flash('error', 'Only JPG, PNG, WebP, GIF, and PDF files are allowed.', 'danger');
            redirect('admin/content');
        }
        if (($file['size'] / 1024 / 1024) > config('app.upload_max_mb')) {
            flash('error', 'Uploaded file exceeds the size limit.', 'danger');
            redirect('admin/content');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'media_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $relativePath = 'uploads/media/' . $filename;
        $target = __DIR__ . '/../../public/' . $relativePath;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            flash('error', 'Media upload failed. Please try again.', 'danger');
            redirect('admin/content');
        }

        (new MediaAsset())->create([
            'title' => Security::cleanString($_POST['title'] ?? pathinfo($file['name'], PATHINFO_FILENAME)),
            'file_path' => $relativePath,
            'mime_type' => $mime,
            'file_size' => (int) $file['size'],
            'uploaded_by' => Auth::id(),
        ]);

        flash('success', 'Media uploaded successfully.', 'success');
        redirect('admin/content');
    }

    private function checkCsrf(): bool
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('admin/content');
            return false;
        }
        return true;
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?: 'item';
        return trim($value, '-');
    }
}
