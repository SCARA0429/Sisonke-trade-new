<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

require_once dirname(__DIR__) . '/includes/payfast_service.php';

$payload = $_POST;
$reference = trim((string) ($payload['m_payment_id'] ?? ''));
$status = strtoupper(trim((string) ($payload['payment_status'] ?? '')));

if ($reference === '') {
    http_response_code(400);
    echo 'Missing PayFast payment reference';
    exit;
}

if ($status !== 'COMPLETE') {
    echo 'PayFast sandbox notification received without COMPLETE status';
    exit;
}

echo 'PayFast sandbox notification received for ' . $reference;
