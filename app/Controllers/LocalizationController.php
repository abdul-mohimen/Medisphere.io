<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Language;
use App\Core\Security;
use App\Models\NotificationPreference;
use App\Services\PageTranslationService;

class LocalizationController
{
    public function set(): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('home');
        }

        $locale = Security::cleanString($_POST['locale'] ?? config('app.default_locale', 'en'));
        Language::setLocale($locale);

        if (Auth::check()) {
            $preferences = (new NotificationPreference())->forUser(Auth::id());
            (new NotificationPreference())->updateForUser(Auth::id(), [
                'email_enabled' => (int) ($preferences['email_enabled'] ?? 1),
                'sms_enabled' => (int) ($preferences['sms_enabled'] ?? 0),
                'in_app_enabled' => (int) ($preferences['in_app_enabled'] ?? 1),
                'appointment_updates' => (int) ($preferences['appointment_updates'] ?? 1),
                'payment_updates' => (int) ($preferences['payment_updates'] ?? 1),
                'security_updates' => (int) ($preferences['security_updates'] ?? 1),
                'marketing_updates' => (int) ($preferences['marketing_updates'] ?? 0),
                'preferred_language' => current_locale(),
            ]);
        }

        $fallback = route_url(Auth::check() ? 'dashboard' : 'home');
        $target = $_SERVER['HTTP_REFERER'] ?? $fallback;
        if (!str_starts_with($target, config('app.base_url'))) {
            $target = $fallback;
        }
        header('Location: ' . $target);
        exit;
    }

    public function translatePage(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $payload = json_decode(file_get_contents('php://input') ?: '[]', true);
        if (!is_array($payload)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON payload.']);
            return;
        }

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($payload['csrf_token'] ?? null);
        if (!CSRF::validate(is_string($token) ? $token : null)) {
            http_response_code(419);
            echo json_encode(['error' => 'Invalid security token.']);
            return;
        }

        $locale = Security::cleanString((string) ($payload['locale'] ?? current_locale()));
        $texts = $payload['texts'] ?? [];
        if (!is_array($texts)) {
            http_response_code(422);
            echo json_encode(['error' => 'Texts must be an array.']);
            return;
        }

        $translations = (new PageTranslationService())->translateMany($texts, $locale);
        echo json_encode([
            'locale' => $locale,
            'translations' => $translations,
        ], JSON_UNESCAPED_UNICODE);
    }
}
