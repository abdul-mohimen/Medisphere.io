<?php
namespace App\Controllers;

use App\Services\UniversalSearch;
use Throwable;

class SearchController
{
    public function suggestions(): void
    {
        header('Content-Type: application/json');

        $query = trim((string) ($_GET['q'] ?? ''));
        if ($query === '') {
            echo json_encode([
                'success' => true,
                'query' => '',
                'results' => [],
                'groups' => [],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $payload = (new UniversalSearch())->search($query);
            echo json_encode([
                'success' => true,
                'query' => $query,
                'results' => $payload['results'],
                'groups' => $payload['groups'],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (Throwable) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'query' => $query,
                'results' => [],
                'groups' => [],
                'message' => 'Universal search is temporarily unavailable.',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
    }
}
