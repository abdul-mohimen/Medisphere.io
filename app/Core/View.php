<?php
namespace App\Core;

class View
{
    private static array $config = [];

    public static function init(array $config): void
    {
        self::$config = $config;
    }

    public static function render(string $view, array $data = [], string $layout = 'main'): void
    {
        $viewPath = __DIR__ . '/../Views/' . $view . '.php';
        $layoutPath = __DIR__ . '/../Views/layouts/' . $layout . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View {$view} not found");
        }

        extract($data);
        $content = self::capture($viewPath, $data);
        require $layoutPath;
    }

    private static function capture(string $path, array $data = []): string
    {
        extract($data);
        ob_start();
        require $path;
        return (string) ob_get_clean();
    }
}
