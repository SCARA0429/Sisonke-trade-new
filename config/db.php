<?php

declare(strict_types=1);

if (!function_exists('sisonke_detect_base_url')) {
    function sisonke_detect_base_url(): string
    {
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptName === '') {
            return '';
        }

        $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($dir === '.' || $dir === '/') {
            return '';
        }

        foreach (['/admin', '/api', '/pages', '/seller', '/setup'] as $section) {
            if (substr($dir, -strlen($section)) === $section) {
                return substr($dir, 0, -strlen($section)) ?: '';
            }
        }

        return $dir;
    }
}

if (!defined('SISONKE_BASE_URL')) {
    $publicBase = getenv('SISONKE_BASE_URL');
    define(
        'SISONKE_BASE_URL',
        is_string($publicBase) && $publicBase !== ''
            ? rtrim($publicBase, '/')
            : sisonke_detect_base_url()
    );
}

$localConfig = __DIR__ . '/db.local.php';
if (is_file($localConfig)) {
    require_once $localConfig;
}

if (!function_exists('sisonke_apply_mysql_url')) {
    function sisonke_apply_mysql_url(string $url): void
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            return;
        }

        if (!defined('DB_HOST')) {
            define('DB_HOST', $parts['host']);
        }
        if (!defined('DB_PORT')) {
            define('DB_PORT', isset($parts['port']) ? (int) $parts['port'] : 3306);
        }
        if (!defined('DB_USER') && isset($parts['user'])) {
            define('DB_USER', rawurldecode($parts['user']));
        }
        if (!defined('DB_PASS') && isset($parts['pass'])) {
            define('DB_PASS', rawurldecode($parts['pass']));
        }
        if (!defined('DB_NAME') && !empty($parts['path'])) {
            define('DB_NAME', ltrim($parts['path'], '/'));
        }
    }
}

if (!defined('DB_HOST')) {
    foreach (['MYSQL_URL', 'DATABASE_URL'] as $urlVar) {
        $mysqlUrl = getenv($urlVar);
        if (is_string($mysqlUrl) && $mysqlUrl !== '') {
            sisonke_apply_mysql_url($mysqlUrl);
            break;
        }
    }
}

if (!defined('DB_HOST')) {
    $host = getenv('SISONKE_DB_HOST') ?: getenv('MYSQLHOST');
    define('DB_HOST', is_string($host) && $host !== '' ? $host : 'localhost');
}
if (!defined('DB_USER')) {
    $user = getenv('SISONKE_DB_USER') ?: getenv('MYSQLUSER');
    define('DB_USER', is_string($user) && $user !== '' ? $user : 'root');
}
if (!defined('DB_PASS')) {
    $pass = getenv('SISONKE_DB_PASS');
    if ($pass === false || $pass === '') {
        $pass = getenv('MYSQLPASSWORD');
    }
    define('DB_PASS', $pass !== false ? $pass : '');
}
if (!defined('DB_NAME')) {
    $name = getenv('SISONKE_DB_NAME') ?: getenv('MYSQLDATABASE');
    define('DB_NAME', is_string($name) && $name !== '' ? $name : 'sisonke_trade');
}
if (!defined('DB_PORT')) {
    $port = getenv('SISONKE_DB_PORT') ?: getenv('MYSQLPORT');
    define('DB_PORT', $port !== false && $port !== '' ? (int) $port : 3306);
}

$dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';

$pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
