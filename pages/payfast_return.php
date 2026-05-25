<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_auth('buyer');
require_once dirname(__DIR__) . '/includes/payfast_service.php';
require_once dirname(__DIR__) . '/includes/i18n.php';

$reference = trim((string) ($_POST['ref'] ?? $_GET['ref'] ?? $_POST['m_payment_id'] ?? $_GET['m_payment_id'] ?? ''));

if ($reference === '') {
    sisonke_flash('danger', sisonke_t('payfast_missing_reference'));
    sisonke_redirect(SISONKE_BASE_URL . '/pages/campaigns.php');
}

$existing = sisonke_payfast_find_transaction($pdo, $reference);
if ($existing !== null) {
    sisonke_flash('success', sisonke_t('payfast_payment_confirmed'));
    sisonke_redirect(SISONKE_BASE_URL . '/pages/dashboard.php');
}

$payload = array_merge($_GET, $_POST);
$paymentStatus = strtoupper(trim((string) ($payload['payment_status'] ?? '')));

if ($paymentStatus === 'COMPLETE' && !empty($payload['signature'])) {
    $result = sisonke_payfast_process_payment($pdo, $payload);
    sisonke_flash($result['success'] ? 'success' : 'danger', $result['message']);
    sisonke_redirect(SISONKE_BASE_URL . '/pages/dashboard.php');
}

if (sisonke_payfast_simulation_allowed() && $paymentStatus === 'COMPLETE') {
    $result = sisonke_payfast_complete_intent($pdo, $reference);
    sisonke_flash($result['success'] ? 'success' : 'danger', $result['message']);
    sisonke_redirect(SISONKE_BASE_URL . '/pages/dashboard.php');
}

sisonke_flash('warning', sisonke_t('payfast_payment_pending'));
sisonke_redirect(SISONKE_BASE_URL . '/pages/dashboard.php');
