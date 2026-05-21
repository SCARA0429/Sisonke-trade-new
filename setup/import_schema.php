<?php

declare(strict_types=1);

/**
 * Import setup/infinityfree.sql via PDO (no mysql CLI required).
 * Run once from Railway shell: php setup/import_schema.php
 */

require_once dirname(__DIR__) . '/config/db.php';

$sqlFile = __DIR__ . '/infinityfree.sql';
if (!is_file($sqlFile)) {
    fwrite(STDERR, "Missing {$sqlFile}\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false || trim($sql) === '') {
    fwrite(STDERR, "SQL file is empty.\n");
    exit(1);
}

$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

$statements = preg_split('/;\s*\n/', $sql) ?: [];
$ran = 0;

foreach ($statements as $statement) {
    $statement = trim($statement);
    if ($statement === '' || str_starts_with($statement, '--')) {
        continue;
    }

    try {
        $pdo->exec($statement);
        $ran++;
    } catch (PDOException $exception) {
        fwrite(STDERR, "Statement failed: {$exception->getMessage()}\n");
        fwrite(STDERR, substr($statement, 0, 120) . "...\n");
        exit(1);
    }
}

echo "Imported {$ran} statements from infinityfree.sql.\n";
