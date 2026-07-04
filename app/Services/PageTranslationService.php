<?php
namespace App\Services;

class PageTranslationService
{
    private const SEPARATOR = '__MEDISPHERE_TRANSLATION_SEPARATOR__';
    private const MAX_ITEMS = 240;
    private const MAX_ITEM_LENGTH = 1200;
    private const MAX_CHUNK_LENGTH = 4500;

    public function translateMany(array $texts, string $targetLocale): array
    {
        $supported = array_keys(config('app.supported_locales', ['en' => 'English']));
        $defaultLocale = config('app.default_locale', 'en');
        if (!in_array($targetLocale, $supported, true)) {
            $targetLocale = $defaultLocale;
        }

        $cleanTexts = $this->cleanTexts($texts);
        if ($targetLocale === $defaultLocale || !$cleanTexts) {
            return array_fill_keys($cleanTexts, null);
        }

        $translations = [];
        $dictionary = $this->localDictionary($targetLocale);
        foreach ($cleanTexts as $text) {
            if (isset($dictionary[$text])) {
                $translations[$text] = $dictionary[$text];
            }
        }

        $pending = array_values(array_diff($cleanTexts, array_keys($translations)));
        if ($pending && config('features.page_translator_remote', true)) {
            foreach ($this->translateRemoteInChunks($pending, $targetLocale) as $source => $translation) {
                if (is_string($translation) && trim($translation) !== '') {
                    $translations[$source] = $translation;
                }
            }
        }

        foreach ($cleanTexts as $text) {
            if (!array_key_exists($text, $translations)) {
                $translations[$text] = null;
            }
        }

        return $translations;
    }

    private function cleanTexts(array $texts): array
    {
        $cleanTexts = [];
        foreach ($texts as $text) {
            if (!is_scalar($text)) {
                continue;
            }
            $text = trim(preg_replace('/\s+/u', ' ', (string) $text) ?? '');
            if ($text === '' || $this->textLength($text) > self::MAX_ITEM_LENGTH || !$this->hasLetters($text)) {
                continue;
            }
            $cleanTexts[$text] = $text;
            if (count($cleanTexts) >= self::MAX_ITEMS) {
                break;
            }
        }
        return array_values($cleanTexts);
    }

    private function textLength(string $text): int
    {
        return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
    }

    private function hasLetters(string $text): bool
    {
        return (bool) preg_match('/\p{L}/u', $text);
    }

    private function localDictionary(string $targetLocale): array
    {
        static $cache = [];
        if (isset($cache[$targetLocale])) {
            return $cache[$targetLocale];
        }

        $defaultLocale = config('app.default_locale', 'en');
        $sourceLines = $this->loadLocale($defaultLocale);
        $targetLines = $this->loadLocale($targetLocale);
        $sourceFlat = $this->flattenStrings($sourceLines);
        $targetFlat = $this->flattenStrings($targetLines);

        $dictionary = [];
        foreach ($sourceFlat as $path => $sourceText) {
            $targetText = $targetFlat[$path] ?? null;
            if (is_string($targetText) && trim($sourceText) !== '' && trim($targetText) !== '') {
                $dictionary[$this->normalizeText($sourceText)] = $targetText;
            }
        }

        $cache[$targetLocale] = $dictionary;
        return $dictionary;
    }

    private function loadLocale(string $locale): array
    {
        $path = __DIR__ . '/../../resources/lang/' . $locale . '.php';
        return file_exists($path) ? (array) require $path : [];
    }

    private function flattenStrings(array $values, string $prefix = ''): array
    {
        $flat = [];
        foreach ($values as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_string($value)) {
                $flat[$path] = $value;
                continue;
            }
            if (is_array($value)) {
                $flat += $this->flattenStrings($value, $path);
            }
        }
        return $flat;
    }

    private function normalizeText(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    private function translateRemoteInChunks(array $texts, string $targetLocale): array
    {
        $translations = [];
        foreach ($this->chunkTexts($texts) as $chunk) {
            $translated = $this->translateRemoteText(implode("\n" . self::SEPARATOR . "\n", $chunk), $targetLocale);
            if (!is_string($translated) || trim($translated) === '') {
                $translations += $this->translateRemoteOneByOne($chunk, $targetLocale);
                continue;
            }

            $parts = preg_split('/\s*' . preg_quote(self::SEPARATOR, '/') . '\s*/u', $translated);
            if (!is_array($parts) || count($parts) !== count($chunk)) {
                $translations += $this->translateRemoteOneByOne($chunk, $targetLocale);
                continue;
            }

            foreach ($chunk as $index => $source) {
                $translation = trim($parts[$index] ?? '');
                if ($translation !== '') {
                    $translations[$source] = $translation;
                }
            }
        }
        return $translations;
    }

    private function chunkTexts(array $texts): array
    {
        $chunks = [];
        $current = [];
        $currentLength = 0;
        $separatorLength = strlen(self::SEPARATOR) + 2;

        foreach ($texts as $text) {
            $length = strlen($text);
            if ($current && ($currentLength + $separatorLength + $length) > self::MAX_CHUNK_LENGTH) {
                $chunks[] = $current;
                $current = [];
                $currentLength = 0;
            }

            $current[] = $text;
            $currentLength += $length + ($currentLength > 0 ? $separatorLength : 0);
        }

        if ($current) {
            $chunks[] = $current;
        }

        return $chunks;
    }

    private function translateRemoteOneByOne(array $texts, string $targetLocale): array
    {
        $translations = [];
        foreach ($texts as $text) {
            $translation = $this->translateRemoteText($text, $targetLocale);
            if (is_string($translation) && trim($translation) !== '') {
                $translations[$text] = trim($translation);
            }
        }
        return $translations;
    }

    private function translateRemoteText(string $text, string $targetLocale): ?string
    {
        $endpoint = config('services.page_translator_endpoint', 'https://translate.googleapis.com/translate_a/single');
        $query = http_build_query([
            'client' => 'gtx',
            'sl' => 'auto',
            'tl' => $this->remoteLocale($targetLocale),
            'dt' => 't',
            'q' => $text,
        ]);
        $response = $this->httpGet(rtrim($endpoint, '?') . '?' . $query);
        if (!is_string($response) || $response === '') {
            return null;
        }

        $payload = json_decode($response, true);
        if (!is_array($payload) || !isset($payload[0]) || !is_array($payload[0])) {
            return null;
        }

        $translation = '';
        foreach ($payload[0] as $segment) {
            if (isset($segment[0]) && is_string($segment[0])) {
                $translation .= $segment[0];
            }
        }

        return trim($translation) !== '' ? trim($translation) : null;
    }

    private function remoteLocale(string $locale): string
    {
        return $locale === 'zh' ? 'zh-CN' : $locale;
    }

    private function httpGet(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT => 'MediSpherePageTranslator/1.0',
            ]);
            $response = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $status >= 200 && $status < 300 && is_string($response) ? $response : null;
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'header' => "User-Agent: MediSpherePageTranslator/1.0\r\n",
            ],
        ]);
        $response = @file_get_contents($url, false, $context);
        return is_string($response) ? $response : null;
    }
}
