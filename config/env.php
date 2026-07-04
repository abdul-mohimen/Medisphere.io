<?php

if (!function_exists('env_value')) {
    function env_value(string $key, $default = null)
    {
        static $loaded = false;

        if (!$loaded) {
            $envPath = dirname(__DIR__) . '/.env';
            if (is_readable($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines ?: [] as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                        continue;
                    }

                    [$name, $value] = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);
                    if ($name === '') {
                        continue;
                    }

                    if (
                        (str_starts_with($value, '"') && str_ends_with($value, '"'))
                        || (str_starts_with($value, "'") && str_ends_with($value, "'"))
                    ) {
                        $value = substr($value, 1, -1);
                    }

                    if (getenv($name) === false) {
                        putenv($name . '=' . $value);
                        $_ENV[$name] = $value;
                        $_SERVER[$name] = $value;
                    }
                }
            }

            $loaded = true;
        }

        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}
