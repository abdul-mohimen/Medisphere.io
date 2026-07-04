<?php
use App\Core\CSRF;
use App\Core\Language;

function config(?string $key = null, $default = null)
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../../config/config.php';
    }

    if ($key === null) {
        return $config;
    }

    $segments = explode('.', $key);
    $value = $config;
    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function app_url(string $path = ''): string
{
    $base = rtrim(config('app.base_url', ''), '/');
    return $base . ($path ? '/' . ltrim($path, '/') : '');
}

function route_url(string $route, array $params = []): string
{
    $query = http_build_query(array_merge(['route' => $route], $params));
    return app_url('index.php?' . $query);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $route, array $params = []): void
{
    header('Location: ' . route_url($route, $params));
    exit;
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['_old'][$key] ?? $default);
}

function set_old(array $data): void
{
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function flash(string $key, ?string $message = null, string $type = 'info')
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = ['message' => $message, 'type' => $type];
        return;
    }

    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }

    $flash = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $flash;
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(CSRF::token()) . '">';
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function is_post(): bool
{
    return request_method() === 'POST';
}

function trans(string $key, array $replace = [], $default = null)
{
    return Language::get($key, $replace, $default);
}

function __(string $key, array $replace = [], ?string $default = null): string
{
    $value = trans($key, $replace, $default);
    return is_string($value) ? $value : ($default ?? $key);
}

function current_locale(): string
{
    return Language::locale();
}

function is_rtl(): bool
{
    return Language::isRtl();
}

function supported_locales(): array
{
    return Language::supportedLocales();
}

function provider_photo_url(string $type, string $seed = ''): string
{
    $photos = [
        'doctor' => [
            'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1582750433449-648ed127bb54?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?auto=format&fit=crop&w=900&q=80',
        ],
        'hospital' => [
            'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1000&q=80',
            'https://images.unsplash.com/photo-1586773860418-d37222d8fce3?auto=format&fit=crop&w=1000&q=80',
        ],
        'care' => [
            'https://images.unsplash.com/photo-1758691462878-6edc3d3da1be?auto=format&fit=crop&w=1400&q=85',
        ],
    ];

    $pool = $photos[$type] ?? $photos['care'];
    $index = abs((int) crc32($seed ?: $type)) % count($pool);
    return $pool[$index];
}
