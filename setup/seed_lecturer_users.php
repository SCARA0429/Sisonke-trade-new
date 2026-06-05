<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_service.php';
require_once dirname(__DIR__) . '/includes/marketplace_service.php';

/**
 * Submission login accounts (safe to re-run; updates passwords and roles).
 */
function sisonke_seed_lecturer_users(PDO $pdo): array
{
    $accounts = [
        [
            'email' => 'sByrneAdmin@gmail.com',
            'password' => 'bestLecturer4eva!',
            'full_name' => 'S Byrne Admin',
            'role' => 'admin',
            'profile_value' => '',
            'permission_level' => 'super_admin',
        ],
        [
            'email' => 'sByrne@gmail.com',
            'password' => 'bestLecturer4always!',
            'full_name' => 'S Byrne',
            'role' => 'user',
            'profile_value' => 'S Byrne',
            'permission_level' => 'support',
        ],
    ];

    $created = [];

    foreach ($accounts as $account) {
        $email = sisonke_normalize_email($account['email']);
        $passwordHash = password_hash($account['password'], PASSWORD_BCRYPT);

        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $userId = (int) ($stmt->fetchColumn() ?: 0);

        if ($userId === 0) {
            $insert = $pdo->prepare(
                'INSERT INTO users (email, password_hash, full_name, role, is_active)
                 VALUES (?, ?, ?, ?, 1)'
            );
            $insert->execute([$email, $passwordHash, $account['full_name'], $account['role']]);
            $userId = (int) $pdo->lastInsertId();
        } else {
            $update = $pdo->prepare(
                'UPDATE users SET password_hash = ?, full_name = ?, role = ?, is_active = 1 WHERE user_id = ?'
            );
            $update->execute([$passwordHash, $account['full_name'], $account['role'], $userId]);
        }

        sisonke_sync_user_profile(
            $pdo,
            $userId,
            $account['role'],
            $account['profile_value'],
            $account['permission_level']
        );

        $created[] = $email . ' (' . $account['role'] . ')';
    }

    return $created;
}
