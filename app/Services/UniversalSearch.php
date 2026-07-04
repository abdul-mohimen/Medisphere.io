<?php
namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Models\Appointment;
use App\Models\ClinicalDocument;
use App\Models\CmsArticle;
use App\Models\CmsFaq;
use App\Models\DiseaseInformation;
use App\Models\Doctor;
use App\Models\HealthArticleCatalog;
use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\MediaAsset;
use App\Models\MedicalReport;
use App\Models\Notification;
use App\Models\PatientHealthMetric;
use App\Models\Payment;
use App\Models\PolicyDocument;
use PDO;
use Throwable;

class UniversalSearch
{
    private const MAX_QUERY_LENGTH = 80;
    private const MAX_RESULTS = 24;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function search(string $query): array
    {
        $query = $this->cleanQuery($query);
        if ($query === '') {
            return ['results' => [], 'groups' => []];
        }

        $items = [];
        $this->indexPlatformRoutes($items, $query);
        $this->indexPublicModels($items, $query);
        $this->indexPublicFiles($items, $query);
        $this->indexAuthorizedRecords($items, $query);

        $ranked = $this->rank($items, $query);
        if (!$ranked) {
            $ranked = $this->fallbackSuggestions($query);
        }

        return [
            'results' => $ranked,
            'groups' => $this->groupedSuggestions($ranked),
        ];
    }

    private function indexPublicModels(array &$items, string $query): void
    {
        $subscriptionAccess = new SubscriptionAccess();

        foreach ((new DiseaseInformation())->search($query) as $disease) {
            $this->add($items, $query, [
                'type' => 'disease',
                'label' => 'Disease',
                'title' => (string) ($disease['disease_name'] ?? ''),
                'subtitle' => trim(implode(' - ', array_filter([
                    $disease['taxonomy_category'] ?? 'Disease guide',
                    $disease['condition_type'] ?? null,
                    !empty($disease['risk_level']) ? ($disease['risk_level'] . ' risk') : null,
                    $this->excerpt((string) (($disease['overview'] ?? '') ?: ($disease['symptoms'] ?? '')), 86),
                ]))),
                'url' => route_url('disease', ['slug' => (string) ($disease['slug'] ?? '')]),
                'icon' => $this->diseaseIcon($disease),
                'scope' => 'Public database',
            ]);
        }

        $articles = $subscriptionAccess->annotateArticles((new HealthArticleCatalog())->searchPublic($query));
        foreach ($this->mergeArticles($articles, (new CmsArticle())->searchPublic($query)) as $article) {
            $this->add($items, $query, [
                'type' => (($article['type'] ?? '') === 'news') ? 'news' : 'article',
                'label' => (($article['type'] ?? '') === 'news') ? 'News' : 'Article',
                'title' => (string) ($article['title'] ?? ''),
                'subtitle' => trim(implode(' - ', array_filter([
                    !empty($article['is_locked']) ? 'Premium' : null,
                    ucfirst((string) ($article['type'] ?? 'Article')),
                    $article['category'] ?? null,
                    $this->excerpt((string) ($article['excerpt'] ?? ''), 95),
                ]))),
                'url' => route_url('article', ['slug' => (string) ($article['slug'] ?? '')]),
                'icon' => !empty($article['is_locked']) ? 'fa-crown' : 'fa-newspaper',
                'scope' => 'Public content',
            ]);
        }

        foreach ((new Doctor())->searchPublic($query) as $doctor) {
            $location = trim(implode(', ', array_filter([$doctor['hospital_city'] ?? '', $doctor['hospital_country'] ?? ''])), ', ');
            $this->add($items, $query, [
                'type' => 'doctor',
                'label' => 'Doctor',
                'title' => (string) ($doctor['name'] ?? ''),
                'subtitle' => trim((string) ($doctor['specialization'] ?? 'Doctor') . ' - ' . (string) ($doctor['hospital_name'] ?? 'Independent practice'), ' -'),
                'url' => route_url('find-healthcare', array_filter([
                    'specialization' => $doctor['specialization'] ?? '',
                    'location' => $location,
                ])) . '#careDoctors',
                'icon' => 'fa-user-doctor',
                'scope' => 'Provider database',
            ]);
        }

        foreach ($this->specialistSuggestions($query) as $specialist) {
            $this->add($items, $query, [
                'type' => 'doctor',
                'label' => 'Specialist',
                'title' => $specialist['title'],
                'subtitle' => $specialist['subtitle'],
                'url' => route_url('find-healthcare', ['specialization' => $specialist['specialization']]) . '#careDoctors',
                'icon' => $specialist['icon'],
                'scope' => 'Specialist index',
            ]);
        }

        foreach ((new Hospital())->searchPublic($query) as $hospital) {
            $mapQuery = trim(implode(' ', array_filter([$hospital['name'] ?? '', $hospital['city'] ?? '', $hospital['country'] ?? ''])));
            $this->add($items, $query, [
                'type' => 'hospital',
                'label' => 'Hospital',
                'title' => (string) ($hospital['name'] ?? ''),
                'subtitle' => trim(implode(', ', array_filter([$hospital['city'] ?? '', $hospital['country'] ?? ''])), ', '),
                'url' => route_url('map', ['location' => $mapQuery, 'focus' => 'hospital']) . '#healthcareExplorerMap',
                'icon' => 'fa-hospital',
                'scope' => 'Facility database',
            ]);
        }

        foreach ($this->globalHospitalSuggestions($query) as $hospital) {
            $this->add($items, $query, [
                'type' => 'hospital',
                'label' => 'Hospital',
                'title' => $hospital['name'],
                'subtitle' => $hospital['subtitle'],
                'url' => route_url('map', ['location' => $hospital['query'], 'focus' => 'global_hospital']) . '#healthcareExplorerMap',
                'icon' => 'fa-hospital-user',
                'scope' => 'Global hospital index',
            ]);
        }

        foreach ((new CmsFaq())->searchPublic($query) as $faq) {
            $this->add($items, $query, [
                'type' => 'faq',
                'label' => 'FAQ',
                'title' => (string) ($faq['question'] ?? ''),
                'subtitle' => 'FAQ - ' . (string) ($faq['category'] ?? 'General'),
                'url' => route_url('faq'),
                'icon' => 'fa-circle-question',
                'scope' => 'Support knowledge base',
            ]);
        }

        foreach ((new PolicyDocument())->searchPublic($query) as $policy) {
            $this->add($items, $query, [
                'type' => 'policy',
                'label' => 'Policy',
                'title' => (string) ($policy['title'] ?? ''),
                'subtitle' => 'Policy - ' . ucwords(str_replace('_', ' ', (string) ($policy['category'] ?? 'Compliance'))),
                'url' => route_url('policy', ['slug' => (string) ($policy['slug'] ?? '')]),
                'icon' => 'fa-shield-halved',
                'scope' => 'Compliance database',
            ]);
        }

        try {
            foreach ((new MediaAsset())->all() as $asset) {
                $this->add($items, $query, [
                    'type' => 'file',
                    'label' => 'Media',
                    'title' => (string) ($asset['title'] ?? basename((string) ($asset['file_path'] ?? ''))),
                    'subtitle' => trim('Media asset - ' . (string) ($asset['mime_type'] ?? 'file')),
                    'url' => app_url((string) ($asset['file_path'] ?? '')),
                    'icon' => str_starts_with((string) ($asset['mime_type'] ?? ''), 'image/') ? 'fa-image' : 'fa-file',
                    'scope' => 'Public media library',
                    'haystack' => implode(' ', [$asset['title'] ?? '', $asset['file_path'] ?? '', $asset['mime_type'] ?? '']),
                ]);
            }
        } catch (Throwable) {
            // Media table may not exist before all migrations are imported.
        }
    }

    private function indexAuthorizedRecords(array &$items, string $query): void
    {
        if (!Auth::check()) {
            return;
        }

        $userId = Auth::id();
        $role = Auth::type();
        if (!$userId) {
            return;
        }

        foreach ((new Notification())->allForUser($userId) as $notification) {
            $this->add($items, $query, [
                'type' => 'private',
                'label' => 'Notification',
                'title' => (string) ($notification['title'] ?? 'Notification'),
                'subtitle' => $this->excerpt((string) ($notification['message'] ?? ''), 110),
                'url' => (string) ($notification['action_url'] ?: route_url('notifications')),
                'icon' => 'fa-bell',
                'scope' => 'Your account',
            ]);
        }

        if ($role === 'patient') {
            foreach ((new Appointment())->forPatient($userId) as $appointment) {
                $this->addAppointment($items, $query, $appointment, 'Your appointments');
            }
            foreach ((new MedicalReport())->byPatient($userId) as $report) {
                $this->add($items, $query, [
                    'type' => 'private',
                    'label' => 'Report',
                    'title' => (string) ($report['original_name'] ?? $report['report_type'] ?? 'Medical report'),
                    'subtitle' => 'Medical report - ' . (string) ($report['report_type'] ?? 'Uploaded file'),
                    'url' => route_url('reports'),
                    'icon' => 'fa-file-medical',
                    'scope' => 'Your records',
                    'haystack' => implode(' ', [$report['original_name'] ?? '', $report['report_type'] ?? '', $report['file_path'] ?? '']),
                ]);
            }
            foreach ((new ClinicalDocument())->byPatient($userId) as $document) {
                $this->addClinicalDocument($items, $query, $document, 'Your clinical records');
            }
            foreach ((new Invoice())->byUser($userId) as $invoice) {
                $this->addInvoice($items, $query, $invoice, 'Your invoices');
            }
            foreach ((new Payment())->byPayer($userId) as $payment) {
                $this->addPayment($items, $query, $payment, 'Your payments');
            }
            foreach ((new PatientHealthMetric())->byPatient($userId, 80) as $metric) {
                $this->add($items, $query, [
                    'type' => 'private',
                    'label' => 'Metric',
                    'title' => ucfirst(str_replace('_', ' ', (string) ($metric['metric_type'] ?? 'Health metric'))),
                    'subtitle' => trim((string) ($metric['value_primary'] ?? '') . ' ' . (string) ($metric['unit'] ?? '') . ' - ' . (string) ($metric['recorded_at'] ?? '')),
                    'url' => route_url('profile'),
                    'icon' => 'fa-chart-line',
                    'scope' => 'Your health profile',
                    'haystack' => implode(' ', $metric),
                ]);
            }
        }

        if ($role === 'doctor') {
            foreach ((new Appointment())->forDoctor($userId) as $appointment) {
                $this->addAppointment($items, $query, $appointment, 'Doctor appointments');
            }
            foreach ((new ClinicalDocument())->byDoctor($userId) as $document) {
                $this->addClinicalDocument($items, $query, $document, 'Doctor clinical records');
            }
            foreach ((new Payment())->byDoctor($userId) as $payment) {
                $this->addPayment($items, $query, $payment, 'Doctor payments');
            }
        }

        if ($role === 'admin') {
            $this->indexAdminModuleFiles($items, $query);
        }
    }

    private function addAppointment(array &$items, string $query, array $appointment, string $scope): void
    {
        $this->add($items, $query, [
            'type' => 'private',
            'label' => 'Appointment',
            'title' => 'Appointment ' . (string) ($appointment['date'] ?? '') . ' ' . (string) ($appointment['time'] ?? ''),
            'subtitle' => trim(implode(' - ', array_filter([
                $appointment['doctor_name'] ?? $appointment['patient_name'] ?? null,
                $appointment['specialization'] ?? null,
                $appointment['status'] ?? null,
            ]))),
            'url' => route_url('appointments'),
            'icon' => 'fa-calendar-check',
            'scope' => $scope,
            'haystack' => implode(' ', $appointment),
        ]);
    }

    private function addClinicalDocument(array &$items, string $query, array $document, string $scope): void
    {
        $this->add($items, $query, [
            'type' => 'private',
            'label' => 'Clinical',
            'title' => (string) ($document['title'] ?? 'Clinical document'),
            'subtitle' => trim(implode(' - ', array_filter([$document['document_type'] ?? null, $document['doctor_name'] ?? null, $document['issue_date'] ?? null]))),
            'url' => route_url('clinical-records/document', ['id' => (int) ($document['id'] ?? 0)]),
            'icon' => 'fa-file-prescription',
            'scope' => $scope,
            'haystack' => implode(' ', array_map(static fn($value): string => is_scalar($value) ? (string) $value : '', $document)),
        ]);
    }

    private function addInvoice(array &$items, string $query, array $invoice, string $scope): void
    {
        $this->add($items, $query, [
            'type' => 'private',
            'label' => 'Invoice',
            'title' => (string) ($invoice['invoice_number'] ?? 'Invoice'),
            'subtitle' => trim((string) ($invoice['status'] ?? '') . ' - ' . (string) ($invoice['amount'] ?? '') . ' ' . (string) ($invoice['currency'] ?? '')),
            'url' => route_url('payments/invoice', ['id' => (int) ($invoice['id'] ?? 0)]),
            'icon' => 'fa-file-invoice',
            'scope' => $scope,
            'haystack' => implode(' ', array_map(static fn($value): string => is_scalar($value) ? (string) $value : '', $invoice)),
        ]);
    }

    private function addPayment(array &$items, string $query, array $payment, string $scope): void
    {
        $this->add($items, $query, [
            'type' => 'private',
            'label' => 'Payment',
            'title' => (string) ($payment['transaction_reference'] ?? 'Payment'),
            'subtitle' => trim((string) ($payment['status'] ?? '') . ' - ' . (string) ($payment['amount'] ?? '') . ' ' . (string) ($payment['currency'] ?? '')),
            'url' => route_url('payments'),
            'icon' => 'fa-credit-card',
            'scope' => $scope,
            'haystack' => implode(' ', array_map(static fn($value): string => is_scalar($value) ? (string) $value : '', $payment)),
        ]);
    }

    private function indexPlatformRoutes(array &$items, string $query): void
    {
        $routes = [
            ['home', 'Home', 'Public homepage, platform overview, care access, articles, and newsletter.', 'fa-house', 'Public route'],
            ['blog', 'Health Articles & Blog', 'Health education articles, news, premium video stories, patient education.', 'fa-newspaper', 'Public route'],
            ['diseases', 'Disease Information Library', 'Global disease, pathogen, chronic illness, diagnostics, and prevention atlas.', 'fa-book-medical', 'Public route'],
            ['find-healthcare', 'Find Healthcare', 'Doctor, specialist, hospital, clinic, and provider directory.', 'fa-stethoscope', 'Public route'],
            ['map', 'Healthcare Map', 'Hospitals, clinics, pharmacies, emergency care, directions, and global hospital markers.', 'fa-map-location-dot', 'Public route'],
            ['faq', 'FAQ Center', 'Support questions, platform usage, patients, doctors, hospitals, compliance.', 'fa-circle-question', 'Public route'],
            ['guidelines', 'Guidelines', 'Help guides, workflows, and patient platform guidance.', 'fa-book-medical', 'Public route'],
            ['policies', 'Policies', 'Privacy, terms, compliance, security, public policy documents.', 'fa-shield-halved', 'Public route'],
            ['login', 'Login', 'Sign in to your account.', 'fa-right-to-bracket', 'Account route'],
            ['register', 'Create Account', 'Register patient, doctor, hospital, or admin account.', 'fa-user-plus', 'Account route'],
            ['dashboard', 'Dashboard', 'Authenticated care workspace and account overview.', 'fa-gauge-high', 'Authenticated route'],
            ['profile', 'Profile & Health Profile', 'Patient profile, medical history, allergies, medications, metrics, insurance.', 'fa-user-doctor', 'Authenticated route'],
            ['appointments', 'Appointments', 'Book, manage, confirm, and review appointments.', 'fa-calendar-check', 'Authenticated route'],
            ['reports', 'Medical Reports', 'Upload, search, and review lab reports and clinical files.', 'fa-file-medical', 'Authenticated route'],
            ['clinical-records', 'Clinical Records', 'Prescriptions, signed documents, verification, and clinical notes.', 'fa-file-prescription', 'Authenticated route'],
            ['record-consents', 'Record Consents', 'Patient consent controls for record sharing.', 'fa-user-shield', 'Authenticated route'],
            ['consultations', 'Consultations', 'Telemedicine hub, consultation rooms, video care workflow.', 'fa-video', 'Authenticated route'],
            ['messages', 'Messages', 'Secure care-team conversations.', 'fa-comments', 'Authenticated route'],
            ['notifications', 'Notifications', 'In-app care and account notifications.', 'fa-bell', 'Authenticated route'],
            ['payments', 'Payments', 'Invoices, checkout, payment history, refunds, subscriptions.', 'fa-credit-card', 'Authenticated route'],
            ['scanner', 'AI Disease Scanner', 'Premium AI image scanner and triage support.', 'fa-microscope', 'Authenticated route'],
            ['hospital-management', 'Hospital Management', 'Departments, beds, inventory, assignments, patient record access.', 'fa-building-circle-check', 'Hospital route'],
            ['admin/content', 'Admin Content Management', 'CMS articles, disease records, FAQs, media, settings.', 'fa-newspaper', 'Admin route'],
            ['admin/compliance', 'Admin Compliance Operations', 'Policies, incidents, privacy requests, acknowledgements.', 'fa-shield-halved', 'Admin route'],
            ['admin/verifications', 'Admin Verifications', 'Doctor and hospital verification workflows.', 'fa-shield-heart', 'Admin route'],
            ['admin/communications', 'Communication Templates', 'Email and SMS templates.', 'fa-envelope-open-text', 'Admin route'],
        ];

        foreach ($routes as [$route, $title, $subtitle, $icon, $scope]) {
            if (!$this->canExposeRoute($route)) {
                continue;
            }
            $this->add($items, $query, [
                'type' => 'route',
                'label' => 'Route',
                'title' => $title,
                'subtitle' => $subtitle,
                'url' => route_url($route),
                'icon' => $icon,
                'scope' => $scope,
                'haystack' => implode(' ', [$route, $title, $subtitle, $scope]),
            ]);
        }
    }

    private function indexPublicFiles(array &$items, string $query): void
    {
        $root = realpath(__DIR__ . '/../../public');
        if (!$root) {
            return;
        }

        $allowedDirs = ['uploads', 'assets'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'mp4', 'wav', 'css', 'js'];
        $limit = 220;
        $count = 0;

        foreach ($allowedDirs as $dir) {
            $path = $root . DIRECTORY_SEPARATOR . $dir;
            if (!is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if ($count >= $limit || !$file->isFile()) {
                    break;
                }
                $extension = strtolower($file->getExtension());
                if (!in_array($extension, $allowedExtensions, true)) {
                    continue;
                }
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                if (!$this->stringMatches($query, $relative)) {
                    continue;
                }
                $count++;
                $this->add($items, $query, [
                    'type' => 'file',
                    'label' => strtoupper($extension),
                    'title' => basename($relative),
                    'subtitle' => 'Public file - ' . dirname($relative),
                    'url' => app_url($relative),
                    'icon' => $this->fileIcon($extension),
                    'scope' => 'Public file index',
                    'haystack' => $relative,
                ]);
            }
        }
    }

    private function indexAdminModuleFiles(array &$items, string $query): void
    {
        $root = realpath(__DIR__ . '/..');
        if (!$root) {
            return;
        }

        foreach (['Controllers', 'Models', 'Services', 'Views'] as $dir) {
            $path = $root . DIRECTORY_SEPARATOR . $dir;
            if (!is_dir($path)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                    continue;
                }
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                if (!$this->stringMatches($query, $relative)) {
                    continue;
                }
                $this->add($items, $query, [
                    'type' => 'module',
                    'label' => 'Module',
                    'title' => basename($relative),
                    'subtitle' => 'Application module - ' . dirname($relative),
                    'url' => route_url('admin/content'),
                    'icon' => $dir === 'Models' ? 'fa-database' : 'fa-code',
                    'scope' => 'Admin module index',
                    'haystack' => $relative,
                ]);
            }
        }
    }

    private function add(array &$items, string $query, array $item): void
    {
        $title = trim((string) ($item['title'] ?? ''));
        $url = trim((string) ($item['url'] ?? ''));
        if ($title === '' || $url === '') {
            return;
        }

        $haystack = trim((string) ($item['haystack'] ?? '') . ' ' . $title . ' ' . (string) ($item['subtitle'] ?? '') . ' ' . (string) ($item['label'] ?? '') . ' ' . (string) ($item['scope'] ?? ''));
        if (!$this->stringMatches($query, $haystack)) {
            return;
        }

        $item['title'] = $title;
        $item['subtitle'] = $this->excerpt((string) ($item['subtitle'] ?? ''), 140);
        $item['url'] = $url;
        $item['icon'] = $item['icon'] ?? 'fa-link';
        $item['score'] = $this->score($query, $title, $haystack, (string) ($item['type'] ?? 'result'));
        $key = strtolower((string) ($item['type'] ?? 'result') . '|' . $url . '|' . $title);
        if (!isset($items[$key]) || ($items[$key]['score'] ?? 0) < $item['score']) {
            $items[$key] = $item;
        }
    }

    private function rank(array $items, string $query): array
    {
        $ranked = array_values($items);
        usort($ranked, static function (array $a, array $b): int {
            $score = ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
            return $score !== 0 ? $score : strcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
        });

        $ranked = array_slice($ranked, 0, self::MAX_RESULTS);
        return array_map(static function (array $item): array {
            unset($item['score'], $item['haystack']);
            return $item;
        }, $ranked);
    }

    private function groupedSuggestions(array $results): array
    {
        $groupMap = [
            'disease' => ['key' => 'diseases', 'title' => 'Diseases, Pathogens & Conditions', 'icon' => 'fa-virus-covid'],
            'article' => ['key' => 'content', 'title' => 'Articles, Blogs & Knowledge', 'icon' => 'fa-newspaper'],
            'news' => ['key' => 'content', 'title' => 'Articles, Blogs & Knowledge', 'icon' => 'fa-newspaper'],
            'doctor' => ['key' => 'care', 'title' => 'Doctors, Specialists & Care Teams', 'icon' => 'fa-user-doctor'],
            'hospital' => ['key' => 'care', 'title' => 'Hospitals, Clinics & Facilities', 'icon' => 'fa-hospital'],
            'route' => ['key' => 'routes', 'title' => 'Routes, Modules & Workflows', 'icon' => 'fa-table-cells-large'],
            'module' => ['key' => 'routes', 'title' => 'Routes, Modules & Workflows', 'icon' => 'fa-code'],
            'file' => ['key' => 'files', 'title' => 'Files & Media Assets', 'icon' => 'fa-folder-open'],
            'faq' => ['key' => 'support', 'title' => 'Support, FAQ & Policies', 'icon' => 'fa-circle-question'],
            'policy' => ['key' => 'support', 'title' => 'Support, FAQ & Policies', 'icon' => 'fa-shield-halved'],
            'private' => ['key' => 'private', 'title' => 'Your Authorized Records', 'icon' => 'fa-lock'],
            'fallback' => ['key' => 'explore', 'title' => 'Explore This Query', 'icon' => 'fa-compass'],
        ];

        $groups = [];
        foreach ($results as $item) {
            $meta = $groupMap[$item['type'] ?? 'fallback'] ?? $groupMap['fallback'];
            $key = $meta['key'];
            if (!isset($groups[$key])) {
                $groups[$key] = ['key' => $key, 'title' => $meta['title'], 'icon' => $meta['icon'], 'items' => []];
            }
            $groups[$key]['items'][] = $item;
        }

        return array_values($groups);
    }

    private function fallbackSuggestions(string $query): array
    {
        return [
            ['type' => 'fallback', 'label' => 'Library', 'title' => 'Explore disease library for "' . $query . '"', 'subtitle' => 'Open the global disease and pathogen atlas filtered by your query.', 'url' => route_url('diseases', ['q' => $query]), 'icon' => 'fa-book-medical', 'scope' => 'Fallback'],
            ['type' => 'fallback', 'label' => 'Care', 'title' => 'Find specialists for "' . $query . '"', 'subtitle' => 'Open the care directory with this term applied as a specialty filter.', 'url' => route_url('find-healthcare', ['specialization' => $query]) . '#careDoctors', 'icon' => 'fa-user-doctor', 'scope' => 'Fallback'],
            ['type' => 'fallback', 'label' => 'Map', 'title' => 'Search hospitals and clinics for "' . $query . '"', 'subtitle' => 'Open the live healthcare map with this location or facility term.', 'url' => route_url('map', ['location' => $query, 'focus' => 'hospital']) . '#healthcareExplorerMap', 'icon' => 'fa-hospital', 'scope' => 'Fallback'],
        ];
    }

    private function score(string $query, string $title, string $haystack, string $type): int
    {
        $term = strtolower($query);
        $titleText = strtolower($title);
        $haystack = strtolower($haystack);
        $score = 0;

        if ($titleText === $term) $score += 160;
        elseif (str_starts_with($titleText, $term)) $score += 120;
        elseif (preg_match('/\b' . preg_quote($term, '/') . '/i', $title) === 1) $score += 96;
        elseif (str_contains($titleText, $term)) $score += 78;
        if (str_contains($haystack, $term)) $score += 32;

        $score += match ($type) {
            'disease' => 18,
            'article', 'news' => 16,
            'doctor', 'hospital' => 14,
            'route' => 12,
            'private' => 11,
            'file', 'module' => 8,
            default => 5,
        };

        return $score;
    }

    private function stringMatches(string $query, string $haystack): bool
    {
        return str_contains(strtolower($haystack), strtolower($query));
    }

    private function canExposeRoute(string $route): bool
    {
        $public = ['home', 'blog', 'diseases', 'find-healthcare', 'map', 'faq', 'guidelines', 'policies', 'login', 'register'];
        if (in_array($route, $public, true)) return true;
        if (!Auth::check()) return false;
        if (str_starts_with($route, 'admin/')) return Auth::type() === 'admin';
        if ($route === 'hospital-management') return Auth::type() === 'hospital';
        if ($route === 'scanner') return Auth::type() === 'patient';
        return true;
    }

    private function mergeArticles(array $catalogMatches, array $cmsMatches): array
    {
        $merged = [];
        foreach (array_merge($catalogMatches, $cmsMatches) as $article) {
            $slug = (string) ($article['slug'] ?? '');
            if ($slug !== '') {
                $merged[$slug] = $article;
            }
        }
        return array_values($merged);
    }

    private function cleanQuery(string $query): string
    {
        $value = trim(strip_tags($query));
        $value = preg_replace('/\s+/', ' ', $value) ?? '';
        return substr($value, 0, self::MAX_QUERY_LENGTH);
    }

    private function excerpt(string $text, int $length = 120): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
        return strlen($text) > $length ? substr($text, 0, $length - 3) . '...' : $text;
    }

    private function fileIcon(string $extension): string
    {
        return match ($extension) {
            'jpg', 'jpeg', 'png', 'webp', 'gif' => 'fa-image',
            'pdf' => 'fa-file-pdf',
            'mp4' => 'fa-circle-play',
            'wav' => 'fa-volume-high',
            'css', 'js' => 'fa-code',
            default => 'fa-file',
        };
    }

    private function diseaseIcon(array $disease): string
    {
        return match ((string) ($disease['taxonomy_category'] ?? '')) {
            'High-Consequence Pathogens' => 'fa-virus-covid',
            'Antimicrobial Resistance' => 'fa-shield-virus',
            'Tropical & Vector-Borne' => 'fa-bug',
            'Cardiovascular' => 'fa-heart-pulse',
            'Respiratory' => 'fa-lungs',
            'Neurology' => 'fa-brain',
            'Oncology' => 'fa-ribbon',
            'Endocrine & Metabolic' => 'fa-droplet',
            'Gastroenterology' => 'fa-capsules',
            'Renal & Urology' => 'fa-prescription-bottle-medical',
            'Mental Health' => 'fa-face-smile',
            'Musculoskeletal' => 'fa-bone',
            'Maternal & Reproductive' => 'fa-baby',
            'Hematology' => 'fa-droplet',
            'Dermatology' => 'fa-hand-dots',
            'Autoimmune & Inflammatory' => 'fa-dna',
            'Critical Care' => 'fa-truck-medical',
            default => 'fa-notes-medical',
        };
    }

    private function specialistSuggestions(string $query): array
    {
        $items = [
            ['title' => 'Infectious Disease Specialist', 'specialization' => 'Infectious Disease', 'subtitle' => 'Pathogens, fever, tuberculosis, HIV, travel infections, antimicrobial resistance.', 'icon' => 'fa-virus'],
            ['title' => 'Cardiologist', 'specialization' => 'Cardiology', 'subtitle' => 'Heart disease, hypertension, coronary disease, arrhythmia, heart failure.', 'icon' => 'fa-heart-pulse'],
            ['title' => 'Pulmonologist', 'specialization' => 'Pulmonology', 'subtitle' => 'Asthma, COPD, pneumonia, respiratory symptoms, oxygen concerns.', 'icon' => 'fa-lungs'],
            ['title' => 'Endocrinologist', 'specialization' => 'Endocrinology', 'subtitle' => 'Diabetes, thyroid disease, obesity, hormone and metabolic disorders.', 'icon' => 'fa-droplet'],
            ['title' => 'Neurologist', 'specialization' => 'Neurology', 'subtitle' => 'Stroke signs, migraine, epilepsy, Parkinson disease, memory changes.', 'icon' => 'fa-brain'],
            ['title' => 'Oncologist', 'specialization' => 'Oncology', 'subtitle' => 'Cancer screening, diagnosis, treatment planning, survivorship care.', 'icon' => 'fa-ribbon'],
            ['title' => 'Gastroenterologist', 'specialization' => 'Gastroenterology', 'subtitle' => 'Liver disease, bowel inflammation, abdominal pain, chronic diarrhea.', 'icon' => 'fa-capsules'],
            ['title' => 'Nephrologist', 'specialization' => 'Nephrology', 'subtitle' => 'Kidney disease, protein in urine, electrolyte problems, dialysis planning.', 'icon' => 'fa-prescription-bottle-medical'],
            ['title' => 'Dermatologist', 'specialization' => 'Dermatology', 'subtitle' => 'Rashes, eczema, psoriasis, skin infection, suspicious lesions.', 'icon' => 'fa-hand-dots'],
            ['title' => 'Rheumatologist', 'specialization' => 'Rheumatology', 'subtitle' => 'Autoimmune disease, lupus, rheumatoid arthritis, inflammatory joint pain.', 'icon' => 'fa-dna'],
            ['title' => 'Psychiatrist', 'specialization' => 'Psychiatry', 'subtitle' => 'Depression, anxiety, safety planning, medication review, crisis support.', 'icon' => 'fa-face-smile'],
            ['title' => 'Maternal-Fetal Medicine Specialist', 'specialization' => 'Maternal Fetal Medicine', 'subtitle' => 'High-risk pregnancy, preeclampsia, fetal monitoring, pregnancy complications.', 'icon' => 'fa-baby'],
        ];
        return array_values(array_filter($items, fn(array $item): bool => $this->stringMatches($query, implode(' ', $item))));
    }

    private function globalHospitalSuggestions(string $query): array
    {
        $items = [
            ['name' => 'Mayo Clinic - Rochester', 'subtitle' => 'Global hospital spotlight - Rochester, United States', 'query' => 'Mayo Clinic Rochester United States'],
            ['name' => 'Cleveland Clinic', 'subtitle' => 'Global hospital spotlight - Cleveland, United States', 'query' => 'Cleveland Clinic Cleveland United States'],
            ['name' => 'Toronto General Hospital', 'subtitle' => 'Global hospital spotlight - Toronto, Canada', 'query' => 'Toronto General Hospital Canada'],
            ['name' => 'Johns Hopkins Hospital', 'subtitle' => 'Top-tier academic medical center - Baltimore, United States', 'query' => 'Johns Hopkins Hospital Baltimore'],
            ['name' => 'Massachusetts General Hospital', 'subtitle' => 'Top-tier academic medical center - Boston, United States', 'query' => 'Massachusetts General Hospital Boston'],
            ['name' => 'Charite - Universitatsmedizin Berlin', 'subtitle' => 'Top-tier university hospital - Berlin, Germany', 'query' => 'Charite Universitatsmedizin Berlin'],
            ['name' => 'Singapore General Hospital', 'subtitle' => 'Major tertiary care center - Singapore', 'query' => 'Singapore General Hospital'],
            ['name' => 'Sheba Medical Center', 'subtitle' => 'Top-tier tertiary hospital - Ramat Gan, Israel', 'query' => 'Sheba Medical Center Israel'],
            ['name' => 'Karolinska University Hospital', 'subtitle' => 'Academic medical center - Stockholm, Sweden', 'query' => 'Karolinska University Hospital Stockholm'],
            ['name' => 'Aga Khan University Hospital', 'subtitle' => 'Regional tertiary care center - Karachi, Pakistan', 'query' => 'Aga Khan University Hospital Karachi'],
        ];
        return array_values(array_filter($items, fn(array $item): bool => $this->stringMatches($query, implode(' ', $item))));
    }
}
