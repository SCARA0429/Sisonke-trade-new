<?php

declare(strict_types=1);

/**
 * Apply schema updates on an existing hosted database after a code deploy.
 * Safe to run multiple times (uses CREATE IF NOT EXISTS / column checks).
 *
 * Railway: open web service Shell → php setup/run_migrations.php
 */

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/messaging_service.php';
require_once dirname(__DIR__) . '/includes/payfast_service.php';
require_once __DIR__ . '/cleanup_production.php';
require_once __DIR__ . '/seed_lecturer_users.php';

echo "Sisonke Trade — running database migrations...\n\n";

try {
    sisonke_bootstrap_marketplace_schema($pdo);
    echo "  marketplace schema: OK\n";

    sisonke_bootstrap_messaging_schema($pdo);
    echo "  messaging schema: OK\n";

    sisonke_bootstrap_payfast_schema($pdo);
    echo "  payfast schema: OK\n";

    $pdo->exec(
        "ALTER TABLE users
         MODIFY COLUMN role ENUM('user','buyer','seller','admin','member') NOT NULL"
    );
    echo "  users.role enum: OK\n";

    $lecturerAccounts = sisonke_seed_lecturer_users($pdo);
    echo "  lecturer accounts: " . implode(', ', $lecturerAccounts) . "\n";

    $cleanup = sisonke_cleanup_production_data($pdo);
    echo "  cleanup: removed {$cleanup['campaigns_removed']} junk campaign(s), {$cleanup['products_removed']} junk product(s)\n";
    echo "  demo discount: applied to {$cleanup['discounts_applied']} School Shoes campaign(s)\n";

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    sort($tables);
    echo "\nTables in database (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo "  - {$table}\n";
    }

    echo "\nMigrations complete.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Migration failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
