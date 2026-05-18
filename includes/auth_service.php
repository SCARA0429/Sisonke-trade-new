<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function sisonke_normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function sisonke_dashboard_path_for_role(string $role): string
{
    return match ($role) {
        'admin' => SISONKE_BASE_URL . '/admin/dashboard.php',
        'seller' => SISONKE_BASE_URL . '/seller/dashboard.php',
        'buyer' => SISONKE_BASE_URL . '/pages/buyers1.php',
        default => SISONKE_BASE_URL . '/pages/login.php',
    };
}

function sisonke_register_user(
    PDO $pdo,
    string $email,
    string $password,
    string $fullName,
    string $role,
    string $extraField
): array {
    $role = strtolower(trim($role));
    if (!in_array($role, ['buyer', 'seller'], true)) {
        return ['success' => false, 'message' => 'Role must be buyer or seller.'];
    }

    $email = sisonke_normalize_email($email);
    $fullName = trim($fullName);
    $extraField = trim($extraField);

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }

    if ($fullName === '' || strlen($fullName) > 120) {
        return ['success' => false, 'message' => 'Please enter your name (max 120 characters).'];
    }

    if ($extraField === '') {
        return [
            'success' => false,
            'message' => $role === 'seller'
                ? 'Business name is required.'
                : 'Delivery address is required.',
        ];
    }

    if ($role === 'seller' && strlen($extraField) > 100) {
        return ['success' => false, 'message' => 'Business name must be 100 characters or fewer.'];
    }

    if ($role === 'buyer' && strlen($extraField) > 255) {
        return ['success' => false, 'message' => 'Delivery address must be 255 characters or fewer.'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
    }

    try {
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already exists.'];
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $pdo->beginTransaction();

        $stmtUser = $pdo->prepare(
            'INSERT INTO users (email, password_hash, full_name, role) VALUES (?, ?, ?, ?)'
        );
        $stmtUser->execute([$email, $passwordHash, $fullName, $role]);
        $newUserId = (int) $pdo->lastInsertId();

        if ($role === 'seller') {
            $stmtSub = $pdo->prepare(
                'INSERT INTO sellers (seller_id, business_name) VALUES (?, ?)'
            );
            $stmtSub->execute([$newUserId, $extraField]);
        } else {
            $stmtSub = $pdo->prepare(
                'INSERT INTO buyers (buyer_id, delivery_address) VALUES (?, ?)'
            );
            $stmtSub->execute([$newUserId, $extraField]);
        }

        $pdo->commit();

        return ['success' => true, 'message' => 'Registration successful!'];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate')) {
            return ['success' => false, 'message' => 'Email already exists.'];
        }

        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

function sisonke_verify_login(PDO $pdo, string $email, string $password): array
{
    $email = sisonke_normalize_email($email);

    if ($email === '' || $password === '') {
        return ['success' => false, 'message' => 'Email and password are required.'];
    }

    $stmt = $pdo->prepare(
        'SELECT user_id, password_hash, full_name, role, is_active FROM users WHERE email = ? LIMIT 1'
    );
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($password, $row['password_hash'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    if (!(bool) $row['is_active']) {
        return ['success' => false, 'message' => 'Your account has been suspended.'];
    }

    $role = strtolower((string) $row['role']);

    return [
        'success' => true,
        'message' => 'OK',
        'user_id' => (int) $row['user_id'],
        'role' => $role,
        'full_name' => (string) $row['full_name'],
    ];
}

function sisonke_login_session(int $userId, string $role, string $email, string $fullName, bool $isActive): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['user_id'] = $userId;
    $_SESSION['user_role'] = $role;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $fullName;
    $_SESSION['is_active'] = $isActive;
}
