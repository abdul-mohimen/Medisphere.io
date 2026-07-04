<?php
namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static array $config = [];
    private static ?PDO $connection = null;

    public static function init(array $config): void
    {
        self::$config = $config;
    }

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                self::$config['host'],
                self::$config['port'],
                self::$config['database'],
                self::$config['charset']
            );

            try {
                self::$connection = new PDO($dsn, self::$config['username'], self::$config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $exception) {
                http_response_code(500);
                echo '<h1>Database Connection Failed</h1>';
                echo '<p>Please import database/schema.sql and update config/config.php.</p>';
                if (config('app.debug')) {
                    echo '<pre>' . htmlspecialchars($exception->getMessage()) . '</pre>';
                }
                exit;
            }
        }

        return self::$connection;
    }
}
