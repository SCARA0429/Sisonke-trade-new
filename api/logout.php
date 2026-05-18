<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
}
session_destroy();

header('Location: ' . SISONKE_BASE_URL . '/pages/login.php');
exit;
