<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_auth('buyer');
require_once dirname(__DIR__) . '/includes/payfast_service.php';
require_once dirname(__DIR__) . '/includes/i18n.php';

$reference = trim((string) ($_GET['ref'] ?? $_POST['ref'] ?? ''));
$intent = $reference !== '' ? sisonke_payfast_get_intent($pdo, $reference) : null;

if ($reference !== '') {
    sisonke_payfast_mark_intent($pdo, $reference, 'cancelled');
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    unset($_SESSION['payfast_intents'][$reference]);
}

sisonke_flash('danger', sisonke_t('payfast_cancelled'));
sisonke_redirect($intent
    ? SISONKE_BASE_URL . '/pages/campaign_detail.php?id=' . (int) $intent['campaign_id']
    : SISONKE_BASE_URL . '/pages/campaigns.php');
