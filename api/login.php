<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/auth_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $email = '';
    $password = '';
    $returnPath = '';
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

    if (is_string($contentType) && stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '{}', true);
        if (is_array($data)) {
            $email = (string) ($data['email'] ?? '');
            $password = (string) ($data['password'] ?? '');
            $returnPath = sisonke_safe_return_path($data['return'] ?? '');
        }
    } else {
        $email = (string) ($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $returnPath = sisonke_safe_return_path($_POST['return'] ?? '');
    }

    $result = sisonke_verify_login($pdo, $email, $password);

    if (!$result['success']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => $result['message']]);
        exit;
    }

    sisonke_login_session(
        $result['user_id'],
        $result['role'],
        sisonke_normalize_email($email),
        $result['full_name'],
        true
    );

    echo json_encode([
        'success' => true,
        'message' => 'Logged in successfully.',
        'redirect' => $returnPath !== '' ? $returnPath : sisonke_dashboard_path_for_role($result['role']),
        'role' => $result['role'],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
