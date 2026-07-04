<?php
namespace App\Core;

class Security
{
    public static function cleanString(?string $value): string
    {
        return trim(filter_var((string) $value, FILTER_SANITIZE_SPECIAL_CHARS));
    }

    public static function cleanEmail(?string $value): string
    {
        return trim(filter_var((string) $value, FILTER_SANITIZE_EMAIL));
    }

    public static function cleanInt($value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    public static function validateRequired(array $fields, array $source): array
    {
        $errors = [];
        foreach ($fields as $field => $label) {
            if (empty(trim((string) ($source[$field] ?? '')))) {
                $errors[$field] = $label . ' is required.';
            }
        }
        return $errors;
    }
}
