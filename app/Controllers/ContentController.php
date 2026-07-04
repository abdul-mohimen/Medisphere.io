<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\CmsArticle;
use App\Models\DiseaseInformation;
use App\Models\HealthArticleCatalog;
use App\Services\SubscriptionAccess;

class ContentController
{
    public function blog(): void
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        $catalog = new HealthArticleCatalog();
        $subscriptionAccess = new SubscriptionAccess();
        $healthArticles = $subscriptionAccess->annotateArticles($query !== '' ? $catalog->searchPublic($query) : $catalog->all());
        $articles = array_values(array_filter($healthArticles, fn(array $item) => ($item['type'] ?? '') === 'blog'));
        $news = array_values(array_filter($healthArticles, fn(array $item) => ($item['type'] ?? '') === 'news'));

        View::render('blog_index', [
            'title' => 'Health Blog & News',
            'healthArticles' => $healthArticles,
            'articles' => $articles,
            'news' => $news,
            'query' => $query,
            'totalArticleCount' => count($catalog->all()),
            'totalImageCount' => $catalog->countByMedia('image'),
            'totalVideoCount' => $catalog->countByMedia('video'),
            ...$subscriptionAccess->modalData(
                'Premium health articles and video stories',
                'Unlock expert video stories, privacy guidance, hospital operations insights, and advanced care-continuity articles with a premium patient plan.',
                route_url('blog'),
                false
            ),
            'metaDescription' => 'Explore healthcare articles, platform news, and patient education content on MediSphere.',
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => route_url('home')],
                ['label' => 'Blog', 'url' => null],
            ],
        ]);
    }

    public function article(): void
    {
        $slug = $_GET['slug'] ?? '';
        $catalog = new HealthArticleCatalog();
        $article = $catalog->findBySlug($slug);
        if ($article) {
            $subscriptionAccess = new SubscriptionAccess();
            $article['required_level'] = $subscriptionAccess->requiredLevelForArticle($article);
            $article['is_premium'] = $article['required_level'] !== 'free';
            $article['is_locked'] = !$subscriptionAccess->canAccessLevel($article['required_level']);

            $related = $subscriptionAccess->annotateArticles($catalog->related($slug, 3));
            $readingTime = max(1, (int) ceil(str_word_count(strip_tags((string) $article['content'])) / 180));

            View::render('health_article_view', [
                'title' => $article['seo_title'] ?: $article['title'],
                'article' => $article,
                'relatedArticles' => $related,
                'readingTime' => $readingTime,
                'metaDescription' => $article['seo_description'] ?: $article['excerpt'],
                'canonicalUrl' => route_url('article', ['slug' => $article['slug']]),
                ...$subscriptionAccess->modalData(
                    'Premium article: ' . $article['title'],
                    'Subscribe to unlock this expert article and the full premium health library.',
                    route_url('article', ['slug' => $article['slug']]),
                    false
                ),
                'breadcrumbs' => [
                    ['label' => 'Home', 'url' => route_url('home')],
                    ['label' => 'Blog', 'url' => route_url('blog')],
                    ['label' => $article['title'], 'url' => null],
                ],
                'schemaData' => [
                    '@context' => 'https://schema.org',
                    '@type' => ($article['type'] ?? '') === 'news' ? 'NewsArticle' : 'Article',
                    'headline' => $article['title'],
                    'description' => $article['seo_description'] ?: $article['excerpt'],
                    'datePublished' => $article['published_at'] ?: $article['created_at'],
                    'image' => $article['hero_image'] ?: ($article['video_poster'] ?: $article['card_image']),
                    'author' => ['@type' => 'Person', 'name' => $article['author_name']],
                ],
            ]);
            return;
        }

        $article = (new CmsArticle())->findBySlug($slug);
        if (!$article || ($article['status'] ?? '') !== 'published') {
            http_response_code(404);
            View::render('error404', ['title' => 'Article Not Found']);
            return;
        }

        $related = (new CmsArticle())->related((int) $article['id'], (string) $article['type'], 3);
        $readingTime = max(1, (int) ceil(str_word_count(strip_tags((string) $article['content'])) / 180));
        View::render('article_view', [
            'title' => $article['seo_title'] ?: $article['title'],
            'article' => $article,
            'relatedArticles' => $related,
            'readingTime' => $readingTime,
            'metaDescription' => $article['seo_description'] ?: ($article['excerpt'] ?: substr(strip_tags((string) $article['content']), 0, 160)),
            'canonicalUrl' => route_url('article', ['slug' => $article['slug']]),
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => route_url('home')],
                ['label' => 'Blog', 'url' => route_url('blog')],
                ['label' => $article['title'], 'url' => null],
            ],
            'schemaData' => [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $article['title'],
                'description' => $article['seo_description'] ?: ($article['excerpt'] ?: substr(strip_tags((string) $article['content']), 0, 160)),
                'datePublished' => $article['published_at'] ?: $article['created_at'],
                'author' => ['@type' => 'Organization', 'name' => config('app.name')],
            ],
        ]);
    }

    public function diseases(): void
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        $model = new DiseaseInformation();
        $diseases = $query !== '' ? $model->search($query) : $model->all('published');
        $diseases = array_map(fn(array $disease): array => $this->enrichDisease($disease), $diseases);
        $subscriptionAccess = new SubscriptionAccess();

        View::render('disease_library', [
            'title' => 'Disease Information Library',
            'diseases' => $diseases,
            'query' => $query,
            'premiumVideoLocked' => !$subscriptionAccess->canAccessLevel('premium'),
            'metaDescription' => 'Search the MediSphere disease knowledge library for symptoms, prevention guidance, and treatment notes.',
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => route_url('home')],
                ['label' => 'Disease Library', 'url' => null],
            ],
        ]);
    }

    public function diseasesApi(): void
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        $model = new DiseaseInformation();
        $diseases = $query !== '' ? $model->search($query) : $model->all('published');
        $diseases = array_map(fn(array $disease): array => $this->enrichDisease($disease), $diseases);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'query' => $query,
            'diseases' => array_map(static fn(array $disease): array => [
                'slug' => $disease['slug'],
                'name' => $disease['disease_name'],
                'summary' => $disease['library_summary'],
                'category' => $disease['library_category'],
                'letter' => $disease['library_letter'],
                'image' => $disease['library_image'],
                'url' => route_url('disease', ['slug' => $disease['slug']]),
                'care_url' => route_url('find-healthcare'),
                'search' => $disease['library_search'],
                'type' => $disease['library_type'],
                'risk_level' => $disease['library_risk_level'],
                'icon' => $disease['library_icon'],
                'diagnostics' => $disease['library_diagnostics'],
                'tags' => $disease['library_tags'],
            ], $diseases),
        ]);
    }

    public function disease(): void
    {
        $slug = $_GET['slug'] ?? '';
        $disease = (new DiseaseInformation())->findBySlug($slug);
        if (!$disease || ($disease['status'] ?? '') !== 'published') {
            http_response_code(404);
            View::render('error404', ['title' => 'Disease Information Not Found']);
            return;
        }

        $disease = $this->enrichDisease($disease);
        $related = array_map(fn(array $item): array => $this->enrichDisease($item), (new DiseaseInformation())->related((int) $disease['id'], 4));
        View::render('disease_view', [
            'title' => $disease['seo_title'] ?: $disease['disease_name'],
            'disease' => $disease,
            'relatedDiseases' => $related,
            'metaDescription' => $disease['seo_description'] ?: substr(strip_tags((string) ($disease['overview'] ?: $disease['symptoms'])), 0, 160),
            'canonicalUrl' => route_url('disease', ['slug' => $disease['slug']]),
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => route_url('home')],
                ['label' => 'Disease Library', 'url' => route_url('diseases')],
                ['label' => $disease['disease_name'], 'url' => null],
            ],
            'schemaData' => [
                '@context' => 'https://schema.org',
                '@type' => 'MedicalCondition',
                'name' => $disease['disease_name'],
                'description' => strip_tags((string) ($disease['overview'] ?: $disease['symptoms'] ?: '')),
            ],
        ]);
    }

    private function enrichDisease(array $disease): array
    {
        $name = trim((string) ($disease['disease_name'] ?? ''));
        $haystack = strtolower(strip_tags(implode(' ', [
            $name,
            $disease['overview'] ?? '',
            $disease['symptoms'] ?? '',
            $disease['causes'] ?? '',
            $disease['prevention'] ?? '',
        ])));

        $disease['library_category'] = $this->diseaseCategory($haystack);
        if (!empty($disease['taxonomy_category'])) {
            $disease['library_category'] = (string) $disease['taxonomy_category'];
        }
        $disease['library_image'] = $this->diseaseImage($disease, $haystack);
        $disease['library_summary'] = $this->diseaseSummary($disease);
        $disease['library_type'] = $disease['condition_type'] ?? $this->conditionType($haystack);
        $disease['library_risk_level'] = $disease['risk_level'] ?? $this->riskLevel($haystack);
        $disease['library_icon'] = $this->categoryIcon($disease['library_category']);
        $disease['library_diagnostics'] = $this->diagnosticTests($disease, $haystack);
        $disease['library_tags'] = $this->riskTags($disease, $haystack);
        $letter = strtoupper(substr($name, 0, 1));
        $disease['library_letter'] = ctype_alpha($letter) ? $letter : '#';
        $disease['library_search'] = trim($haystack . ' ' . strtolower(implode(' ', [
            $disease['library_category'] ?? '',
            $disease['library_type'] ?? '',
            $disease['library_risk_level'] ?? '',
            implode(' ', $disease['library_diagnostics'] ?? []),
            implode(' ', $disease['library_tags'] ?? []),
        ])));

        return $disease;
    }

    private function diseaseCategory(string $haystack): string
    {
        $categories = [
            'High-Consequence Pathogens' => ['ebola', 'marburg', 'nipah', 'rabies', 'plague', 'hemorrhagic fever'],
            'Antimicrobial Resistance' => ['resistant', 'resistance', 'carbapenem', 'cre', 'mrsa', 'gonorrhea', 'amr'],
            'Tropical & Vector-Borne' => ['dengue', 'malaria', 'yellow fever', 'zika', 'lyme', 'vector', 'mosquito', 'tick'],
            'Cardiovascular' => ['heart', 'cardio', 'hypertension', 'blood pressure', 'chest pain', 'coronary', 'stroke', 'failure'],
            'Dermatology' => ['skin', 'eczema', 'dermat', 'rash', 'acne', 'psoriasis', 'lesion'],
            'Neurology' => ['brain', 'neuro', 'migraine', 'epilepsy', 'seizure', 'nerve', 'alzheimer', 'parkinson', 'stroke'],
            'Oncology' => ['cancer', 'tumor', 'oncology', 'hpv'],
            'Mental Health' => ['depression', 'anxiety', 'panic', 'mental health', 'suicidal'],
            'Musculoskeletal' => ['arthritis', 'joint', 'bone', 'musculoskeletal'],
            'Gastroenterology' => ['liver', 'bowel', 'celiac', 'digestive', 'hepatitis', 'diarrhea', 'abdominal'],
            'Renal & Urology' => ['kidney', 'renal', 'urine', 'urology'],
            'Maternal & Reproductive' => ['pregnancy', 'preeclampsia', 'pcos', 'cervical', 'ovary', 'reproductive'],
            'Hematology' => ['sickle', 'anemia', 'blood', 'hemoglobin'],
            'Autoimmune & Inflammatory' => ['lupus', 'autoimmune', 'inflammatory', 'immune-mediated'],
            'Infectious Diseases' => ['infection', 'fever', 'viral', 'bacterial', 'covid', 'tuberculosis', 'hepatitis', 'typhoid', 'cholera', 'measles', 'meningitis'],
            'Respiratory' => ['asthma', 'lung', 'respiratory', 'breathing', 'copd', 'bronchitis', 'pneumonia'],
            'Endocrine & Metabolic' => ['diabetes', 'thyroid', 'hormone', 'blood sugar', 'endocrine', 'insulin', 'obesity', 'metabolic'],
            'Critical Care' => ['sepsis', 'shock', 'critical care'],
        ];

        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($haystack, $keyword)) {
                    return $category;
                }
            }
        }

        return 'General Care';
    }

    private function diseaseImage(array $disease, string $haystack): string
    {
        if (!empty($disease['featured_image_path'])) {
            return app_url($disease['featured_image_path']);
        }

        if (str_contains($haystack, 'diabetes') || str_contains($haystack, 'blood sugar')) {
            return 'https://images.unsplash.com/photo-1579154204601-01588f351e67?auto=format&fit=crop&w=1200&q=85';
        }
        if (str_contains($haystack, 'virus') || str_contains($haystack, 'bacterial') || str_contains($haystack, 'pathogen') || str_contains($haystack, 'infection')) {
            return 'https://images.unsplash.com/photo-1581093458791-9f3c3900df7b?auto=format&fit=crop&w=1200&q=85';
        }
        if (str_contains($haystack, 'heart') || str_contains($haystack, 'cardio') || str_contains($haystack, 'hypertension')) {
            return 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?auto=format&fit=crop&w=1200&q=85';
        }
        if (str_contains($haystack, 'cancer') || str_contains($haystack, 'oncology')) {
            return 'https://images.unsplash.com/photo-1579154341098-e4e158cc7f55?auto=format&fit=crop&w=1200&q=85';
        }
        if (str_contains($haystack, 'brain') || str_contains($haystack, 'neuro') || str_contains($haystack, 'stroke')) {
            return 'https://images.unsplash.com/photo-1559757175-5700dde675bc?auto=format&fit=crop&w=1200&q=85';
        }
        if (str_contains($haystack, 'pregnancy') || str_contains($haystack, 'reproductive')) {
            return 'https://images.unsplash.com/photo-1559757175-0eb30cd8c063?auto=format&fit=crop&w=1200&q=85';
        }
        if (str_contains($haystack, 'asthma') || str_contains($haystack, 'lung') || str_contains($haystack, 'respiratory')) {
            return 'https://images.unsplash.com/photo-1581594693702-fbdc51b2763b?auto=format&fit=crop&w=1200&q=85';
        }
        if (str_contains($haystack, 'skin') || str_contains($haystack, 'eczema') || str_contains($haystack, 'dermat')) {
            return 'https://images.unsplash.com/photo-1582719471384-894fbb16e074?auto=format&fit=crop&w=1200&q=85';
        }

        return 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=1200&q=85';
    }

    private function conditionType(string $haystack): string
    {
        if (str_contains($haystack, 'viral')) return 'Viral disease';
        if (str_contains($haystack, 'bacterial')) return 'Bacterial disease';
        if (str_contains($haystack, 'parasite') || str_contains($haystack, 'parasitic')) return 'Parasitic disease';
        if (str_contains($haystack, 'cancer')) return 'Cancer';
        if (str_contains($haystack, 'autoimmune')) return 'Autoimmune condition';
        if (str_contains($haystack, 'chronic')) return 'Chronic condition';
        return 'Clinical condition';
    }

    private function riskLevel(string $haystack): string
    {
        if (str_contains($haystack, 'emergency') || str_contains($haystack, 'critical') || str_contains($haystack, 'life-threatening') || str_contains($haystack, 'sepsis') || str_contains($haystack, 'stroke')) {
            return 'Critical';
        }
        if (str_contains($haystack, 'severe') || str_contains($haystack, 'hospital') || str_contains($haystack, 'fatal') || str_contains($haystack, 'bleeding')) {
            return 'High';
        }
        if (str_contains($haystack, 'chronic') || str_contains($haystack, 'monitoring')) {
            return 'Moderate';
        }
        return 'Low';
    }

    private function categoryIcon(string $category): string
    {
        return match ($category) {
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
            default => 'fa-book-medical',
        };
    }

    private function diagnosticTests(array $disease, string $haystack): array
    {
        if (!empty($disease['diagnostic_tests']) && is_array($disease['diagnostic_tests'])) {
            return array_slice($disease['diagnostic_tests'], 0, 4);
        }

        if (str_contains($haystack, 'diabetes')) return ['A1C', 'Fasting glucose', 'Kidney screen'];
        if (str_contains($haystack, 'blood pressure') || str_contains($haystack, 'hypertension')) return ['BP log', 'Kidney tests', 'ECG'];
        if (str_contains($haystack, 'virus') || str_contains($haystack, 'infection')) return ['PCR or antigen', 'CBC', 'Clinical exam'];
        if (str_contains($haystack, 'cancer')) return ['Imaging', 'Biopsy', 'Staging tests'];
        if (str_contains($haystack, 'asthma') || str_contains($haystack, 'copd')) return ['Spirometry', 'Pulse oximetry', 'Action plan review'];
        return ['Clinical history', 'Physical exam', 'Targeted labs'];
    }

    private function riskTags(array $disease, string $haystack): array
    {
        if (!empty($disease['risk_tags']) && is_array($disease['risk_tags'])) {
            return array_slice($disease['risk_tags'], 0, 4);
        }

        $tags = [];
        foreach ([
            'Emergency' => ['emergency', 'urgent', 'critical'],
            'Chronic' => ['chronic', 'long-term'],
            'Vaccine-preventable' => ['vaccine', 'vaccination'],
            'Respiratory' => ['respiratory', 'breathing', 'lung'],
            'Travel medicine' => ['travel', 'mosquito', 'tropical'],
        ] as $label => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($haystack, $needle)) {
                    $tags[] = $label;
                    break;
                }
            }
        }

        return $tags ?: ['Education', 'Clinician review'];
    }

    private function diseaseSummary(array $disease): string
    {
        $source = ($disease['overview'] ?? '') ?: ($disease['symptoms'] ?? '');
        $text = trim(strip_tags((string) $source));
        if ($text === '') {
            return 'Patient-friendly symptoms, prevention, treatment, and escalation guidance.';
        }

        return strlen($text) > 180 ? substr($text, 0, 180) . '...' : $text;
    }
}
