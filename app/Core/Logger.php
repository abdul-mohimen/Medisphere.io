<?php
namespace App\Core;

class Logger
{
    private static string $logPath;
    private static bool $initialized = false;

    public static function init(string $logPath): void
    {
        self::$logPath = rtrim($logPath, '/');
        self::$initialized = true;
        
        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0755, true);
        }
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        if (config('app.debug', false)) {
            self::log('DEBUG', $message, $context);
        }
    }

    private static function log(string $level, string $message, array $context = []): void
    {
        if (!self::$initialized) {
            self::init(__DIR__ . '/../../storage/logs');
        }

        $timestamp = date('Y-m-d H:i:s');
        $logFile = self::$logPath . '/' . strtolower($level) . '.log';
        
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    public static function logException(\Throwable $exception, string $context = ''): void
    {
        self::error($exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context
        ]);
    }
}
