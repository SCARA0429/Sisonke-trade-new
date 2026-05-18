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

if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('SISONKE_DB_HOST') ?: 'localhost');
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('SISONKE_DB_USER') ?: 'root');
}
if (!defined('DB_PASS')) {
    $dbPassEnv = getenv('SISONKE_DB_PASS');
    define('DB_PASS', $dbPassEnv !== false ? $dbPassEnv : '');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('SISONKE_DB_NAME') ?: 'sisonke_trade');
}

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

$pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
