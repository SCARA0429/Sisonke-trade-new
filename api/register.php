<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/auth_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    $fullName = '';
    $email = '';
    $password = '';

    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

    if (is_string($contentType) && stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '{}', true);
        if (is_array($data)) {
            $fullName = trim((string) ($data['full_name'] ?? ''));
            $emailCandidate = filter_var(trim((string) ($data['email'] ?? '')), FILTER_VALIDATE_EMAIL);
            $email = $emailCandidate !== false ? $emailCandidate : '';
            $password = (string) ($data['password'] ?? '');
        }
    } else {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $emailCandidate = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $email = $emailCandidate !== false ? $emailCandidate : '';
        $password = (string) ($_POST['password'] ?? '');
    }

    $result = sisonke_register_user($pdo, $email, $password, $fullName);

    if (!$result['success']) {
        http_response_code(400);
    }

    echo json_encode(['success' => $result['success'], 'message' => $result['message']]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
