<?php
$config = require __DIR__ . '/../config/config.php';

date_default_timezone_set($config['app']['timezone']);

if (session_status() === PHP_SESSION_NONE) {
    session_name($config['security']['session_name']);
    
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $cookieParams = [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Strict'
    ];
    
    session_set_cookie_params($cookieParams);
    session_start();
}

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

require_once __DIR__ . '/Core/helpers.php';

App\Core\Database::init($config['db']);
App\Core\Auth::init($config);
App\Core\Language::init($config);
App\Core\View::init($config);
App\Core\Logger::init(__DIR__ . '/../storage/logs');
    