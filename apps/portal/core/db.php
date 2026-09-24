<?php
/**
 * Database Helper Class (SQLite fallback for dev/testing, PDO MySQL for prod)
 */

if (!defined('INFOLAYER_PORTAL')) {
    define('INFOLAYER_PORTAL', true);
}

class PortalDB {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $config_file = __DIR__ . '/../config/app.config.php';
            if (file_exists($config_file)) {
                $config = require $config_file;
                $driver = $config['db_driver'] ?? 'mysql';

                if ($driver === 'sqlite') {
                    $dsn = "sqlite:" . $config['db_file'];
                    self::$instance = new PDO($dsn);
                } else {
                    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
                    self::$instance = new PDO($dsn, $config['db_user'], $config['db_pass'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]);
                }
            } else {
                // SQLite fallback for standalone portal testing
                $sqlite_path = __DIR__ . '/../storage/secure/portal.sqlite';
                $dsn = "sqlite:" . $sqlite_path;
                self::$instance = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            }
        }
        return self::$instance;
    }
}
