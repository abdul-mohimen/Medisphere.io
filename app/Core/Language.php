<?php
namespace App\Core;

class Language
{
    private static array $config = [];
    private static string $locale = 'en';
    private static array $lines = [];
    private static array $fallbackLines = [];

    public static function init(array $config): void
    {
        self::$config = $config;
        $supported = array_keys($config['app']['supported_locales'] ?? ['en' => 'English']);
        $default = $config['app']['default_locale'] ?? 'en';

        $locale = $_SESSION['_locale'] ?? $_COOKIE['medisphere_locale'] ?? self::detectBrowserLocale($supported, $default);
        if (!in_array($locale, $supported, true)) {
            $locale = $default;
        }

        self::setLocale($locale, false);
    }

    public static function setLocale(string $locale, bool $persist = true): void
    {
        $supported = array_keys(self::$config['app']['supported_locales'] ?? ['en' => 'English']);
        $default = self::$config['app']['default_locale'] ?? 'en';
        self::$locale = in_array($locale, $supported, true) ? $locale : $default;
        self::$lines = self::loadLocale(self::$locale);
        self::$fallbackLines = self::loadLocale($default);

        if ($persist) {
            $_SESSION['_locale'] = self::$locale;
            setcookie('medisphere_locale', self::$locale, time() + (86400 * 30), '/');
        }
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    public static function isRtl(): bool
    {
        return in_array(self::$locale, self::$config['app']['rtl_locales'] ?? [], true);
    }

    public static function supportedLocales(): array
    {
        return self::$config['app']['supported_locales'] ?? ['en' => 'English'];
    }

    public static function get(string $key, array $replace = [], $default = null)
    {
        $value = self::arrayGet(self::$lines, $key);
        if ($value === null) {
            $value = self::arrayGet(self::$fallbackLines, $key);
        }
        if ($value === null) {
            $value = $default ?? $key;
        }

        if (is_string($value)) {
            foreach ($replace as $placeholder => $replacement) {
                $value = str_replace(':' . $placeholder, (string) $replacement, $value);
            }
        }

        return $value;
    }

    private static function loadLocale(string $locale): array
    {
        $path = __DIR__ . '/../../resources/lang/' . $locale . '.php';
        return file_exists($path) ? (array) require $path : [];
    }

    private static function detectBrowserLocale(array $supported, string $default): string
    {
        $header = strtolower((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
        foreach (explode(',', $header) as $part) {
            $candidate = substr(trim($part), 0, 2);
            if (in_array($candidate, $supported, true)) {
                return $candidate;
            }
        }
        return $default;
    }

    private static function arrayGet(array $array, string $key)
    {
        $segments = explode('.', $key);
        $value = $array;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}
