<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/payfast_service.php';
require_once dirname(__DIR__) . '/includes/i18n.php';

header('Content-Type: text/plain; charset=utf-8');

$payload = $_POST;
if ($payload === []) {
    http_response_code(400);
    echo 'Empty PayFast notification';
    exit;
}

$result = sisonke_payfast_process_payment($pdo, $payload);

http_response_code($result['success'] ? 200 : 400);
echo $result['success'] ? 'OK' : 'FAILED';
