<?php

declare(strict_types=1);

require_once __DIR__ . '/auth_service.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function require_auth(?string $requiredRole = null): void
{
    $loggedIn = !empty($_SESSION['user_id']);

    if (!$loggedIn) {
        header('Location: ' . SISONKE_BASE_URL . '/pages/login.php');
        exit;
    }

    if (isset($_SESSION['is_active']) && $_SESSION['is_active'] === false) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
        }
        session_destroy();
        header('Location: ' . SISONKE_BASE_URL . '/pages/login.php?error=account_suspended');
        exit;
    }

    if ($requiredRole !== null) {
        $currentRole = (string) ($_SESSION['user_role'] ?? '');
        if (!sisonke_role_can_act_as($currentRole, $requiredRole)) {
            header('Location: ' . sisonke_dashboard_path_for_role($currentRole));
            exit;
        }
    }
}
