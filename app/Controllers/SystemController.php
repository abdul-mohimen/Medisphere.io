<?php
namespace App\Controllers;

use App\Models\CmsArticle;
use App\Models\DiseaseInformation;
use App\Models\PolicyDocument;

class SystemController
{
    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=UTF-8');

        $urls = [
            route_url('home'),
            route_url('faq'),
            route_url('guidelines'),
            route_url('blog'),
            route_url('diseases'),
            route_url('map'),
            route_url('policies'),
        ];

        foreach ((new CmsArticle())->all('published') as $article) {
            $urls[] = route_url('article', ['slug' => $article['slug']]);
        }
        foreach ((new DiseaseInformation())->all('published') as $disease) {
            $urls[] = route_url('disease', ['slug' => $disease['slug']]);
        }
        foreach ((new PolicyDocument())->publicAll() as $policy) {
            $urls[] = route_url('policy', ['slug' => $policy['slug']]);
        }

        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $url) {
            echo '<url><loc>' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</loc></url>';
        }
        echo '</urlset>';
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Sitemap: " . app_url('index.php?route=sitemap.xml') . "\n";
    }
}
