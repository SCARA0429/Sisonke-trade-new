<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/marketplace_service.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (($_SESSION['user_role'] ?? '') !== 'buyer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Log in as a buyer to confirm delivery.']);
    exit;
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
$data = $_POST;
if (is_string($contentType) && stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw ?: '{}', true);
    $data = is_array($decoded) ? $decoded : [];
}

$result = sisonke_confirm_delivery(
    $pdo,
    (int) $_SESSION['user_id'],
    (int) ($data['participant_id'] ?? 0)
);

if (!$result['success']) {
    http_response_code(400);
}

echo json_encode($result);
