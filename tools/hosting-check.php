<?php

declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo '<pre>';
echo "Sisonke Trade hosting check\n\n";
echo 'PHP version: ' . PHP_VERSION . "\n";

$required = [
    'pdo' => extension_loaded('pdo'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'session' => extension_loaded('session'),
    'mbstring' => extension_loaded('mbstring'),
];

foreach ($required as $extension => $loaded) {
    echo $extension . ': ' . ($loaded ? 'OK' : 'MISSING') . "\n";
}

echo "\nDatabase check:\n";

try {
    require_once dirname(__DIR__) . '/config/db.php';
    $pdo->query('SELECT 1');
    echo "Database connection: OK\n";
} catch (Throwable $exception) {
    echo "Database connection: FAILED\n";
    echo get_class($exception) . ': ' . $exception->getMessage() . "\n";
}

echo '</pre>';
