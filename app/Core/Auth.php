<?php
namespace App\Core;

use App\Models\User;

class Auth
{
    private static array $config = [];

    public static function init(array $config): void
    {
        self::$config = $config;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = $user['user_type'];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return self::check() ? (int) $_SESSION['user_id'] : null;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return (new User())->findById(self::id());
    }

    public static function type(): ?string
    {
        return $_SESSION['user_type'] ?? null;
    }

    public static function requireLogin(?array $roles = null): void
    {
        if (!self::check()) {
            $returnTo = self::rememberIntendedUrl();
            flash('error', 'Please sign in to continue.', 'danger');
            redirect('login', ['return_to' => $returnTo]);
        }

        if ($roles && !in_array(self::type(), $roles, true)) {
            flash('error', 'You do not have permission to access that page.', 'danger');
            redirect('dashboard');
        }
    }

    public static function rememberIntendedUrl(?string $url = null): string
    {
        $returnTo = self::safeReturnUrl($url ?: self::currentRequestUrl(), route_url('dashboard'));
        if (!self::isAuthUrl($returnTo)) {
            $_SESSION['return_to'] = $returnTo;
        }

        return $_SESSION['return_to'] ?? route_url('dashboard');
    }

    public static function intendedUrl(?string $fallback = null): string
    {
        return self::safeReturnUrl((string) ($_SESSION['return_to'] ?? ''), $fallback ?: route_url('dashboard'));
    }

    public static function consumeIntendedUrl(?string $fallback = null): string
    {
        $returnTo = self::intendedUrl($fallback ?: route_url('dashboard'));
        unset($_SESSION['return_to']);
        return $returnTo;
    }

    public static function clearIntendedUrl(): void
    {
        unset($_SESSION['return_to']);
    }

    public static function safeReturnUrl(string $url, ?string $fallback = null): string
    {
        $url = trim($url);
        $fallback = $fallback ?: route_url('dashboard');
        if ($url === '') {
            return $fallback;
        }

        $base = rtrim(app_url(), '/');
        $baseParts = parse_url($base) ?: [];
        $scheme = $baseParts['scheme'] ?? 'http';
        $host = $baseParts['host'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $port = isset($baseParts['port']) ? ':' . $baseParts['port'] : '';
        $origin = $scheme . '://' . $host . $port;
        $basePath = rtrim((string) ($baseParts['path'] ?? ''), '/');

        if (str_starts_with($url, $base)) {
            return $url;
        }

        if (preg_match('#^https?://#i', $url)) {
            $parts = parse_url($url) ?: [];
            $path = (string) ($parts['path'] ?? '');
            $sameOrigin = strtolower((string) ($parts['scheme'] ?? '')) === strtolower($scheme)
                && strtolower((string) ($parts['host'] ?? '')) === strtolower($host)
                && (int) ($parts['port'] ?? 0) === (int) ($baseParts['port'] ?? 0);
            $insideApp = $basePath === '' || $path === $basePath || str_starts_with($path, $basePath . '/');
            return $sameOrigin && $insideApp ? $url : $fallback;
        }

        if (str_starts_with($url, '/')) {
            if ($basePath !== '' && ($url === $basePath || str_starts_with($url, $basePath . '/'))) {
                return $origin . $url;
            }

            return $base . $url;
        }

        if (str_starts_with($url, 'index.php')) {
            return app_url($url);
        }

        return $fallback;
    }

    private static function currentRequestUrl(): string
    {
        $base = parse_url(app_url()) ?: [];
        $scheme = (string) ($base['scheme'] ?? 'http');
        $host = ($base['host'] ?? 'localhost') . (isset($base['port']) ? ':' . $base['port'] : '');
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        return $scheme . '://' . $host . $requestUri;
    }

    private static function isAuthUrl(string $url): bool
    {
        $query = (string) (parse_url($url, PHP_URL_QUERY) ?? '');
        parse_str($query, $params);
        $route = (string) ($params['route'] ?? '');

        return in_array($route, ['auth', 'login', 'register', 'logout', 'reset-password'], true);
    }
}
