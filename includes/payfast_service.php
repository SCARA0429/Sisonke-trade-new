<?php

declare(strict_types=1);

require_once __DIR__ . '/marketplace_service.php';

function sisonke_payfast_endpoint(): string
{
    return getenv('SISONKE_PAYFAST_ENDPOINT') ?: 'https://sandbox.payfast.co.za/eng/process';
}

function sisonke_payfast_merchant_id(): string
{
    return getenv('SISONKE_PAYFAST_MERCHANT_ID') ?: '10000100';
}

function sisonke_payfast_merchant_key(): string
{
    return getenv('SISONKE_PAYFAST_MERCHANT_KEY') ?: '46f0cd694581a';
}

function sisonke_payfast_passphrase(): string
{
    return getenv('SISONKE_PAYFAST_PASSPHRASE') ?: '';
}

function sisonke_public_url(string $path): string
{
    $configured = rtrim((string) (getenv('SISONKE_PUBLIC_URL') ?: ''), '/');
    if ($configured !== '') {
        return $configured . '/' . ltrim($path, '/');
    }

    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

    return $scheme . '://' . $host . SISONKE_BASE_URL . '/' . ltrim($path, '/');
}

function sisonke_payfast_uses_local_urls(): bool
{
    return (bool) preg_match('/^https?:\/\/(localhost|127\.0\.0\.1|.*\.local)(:|\/)/i', sisonke_public_url('pages/payfast_return.php'));
}

function sisonke_payfast_signature(array $data, string $passphrase = ''): string
{
    $pairs = [];
    foreach ($data as $key => $value) {
        if ($key === 'signature' || $value === '' || $value === null) {
            continue;
        }
        $pairs[] = $key . '=' . urlencode(trim((string) $value));
    }

    if ($passphrase !== '') {
        $pairs[] = 'passphrase=' . urlencode(trim($passphrase));
    }

    return md5(implode('&', $pairs));
}

function sisonke_payfast_reference(): string
{
    return 'PF-ST-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function sisonke_payfast_safe_email(string $email): string
{
    $email = trim($email);
    if (
        filter_var($email, FILTER_VALIDATE_EMAIL)
        && !preg_match('/\.(test|local)$/i', substr($email, (int) strrpos($email, '@') + 1))
    ) {
        return $email;
    }

    return 'buyer@sisonketrade.co.za';
}

function sisonke_payfast_create_intent(array $campaign, int $buyerId, string $buyerEmail, string $buyerName, int $quantity): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $quantity = max(1, min(50, $quantity));
    $amount = number_format((float) $campaign['campaign_price'] * $quantity, 2, '.', '');
    $reference = sisonke_payfast_reference();
    $nameParts = preg_split('/\s+/', trim($buyerName), 2) ?: [];
    $firstName = $nameParts[0] ?? 'Sisonke';
    $lastName = $nameParts[1] ?? 'Buyer';

    $intent = [
        'reference' => $reference,
        'campaign_id' => (int) $campaign['campaign_id'],
        'buyer_id' => $buyerId,
        'buyer_email' => $buyerEmail,
        'buyer_name' => $buyerName,
        'quantity' => $quantity,
        'amount' => $amount,
        'item_name' => substr((string) $campaign['product_name'], 0, 100),
        'item_description' => substr('Sisonke Trade campaign from ' . (string) $campaign['business_name'], 0, 255),
        'created_at' => time(),
    ];

    $_SESSION['payfast_intents'][$reference] = $intent;

    $data = [
        'merchant_id' => sisonke_payfast_merchant_id(),
        'merchant_key' => sisonke_payfast_merchant_key(),
        'name_first' => $firstName,
        'name_last' => $lastName,
        'email_address' => sisonke_payfast_safe_email($buyerEmail),
        'm_payment_id' => $reference,
        'amount' => $amount,
        'item_name' => $intent['item_name'],
        'item_description' => $intent['item_description'],
        'custom_int1' => (string) $campaign['campaign_id'],
        'custom_int2' => (string) $quantity,
        'custom_str1' => (string) $buyerId,
    ];

    if (!sisonke_payfast_uses_local_urls()) {
        $data = array_slice($data, 0, 2, true)
            + [
                'return_url' => sisonke_public_url('pages/payfast_return.php?ref=' . rawurlencode($reference)),
                'cancel_url' => sisonke_public_url('pages/payfast_cancel.php?ref=' . rawurlencode($reference)),
                'notify_url' => sisonke_public_url('api/payfast_notify.php'),
            ]
            + array_slice($data, 2, null, true);
    }

    $passphrase = sisonke_payfast_passphrase();
    if ($passphrase !== '') {
        $data['signature'] = sisonke_payfast_signature($data, $passphrase);
    }

    return ['intent' => $intent, 'data' => $data];
}

function sisonke_payfast_get_intent(string $reference): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $intent = $_SESSION['payfast_intents'][$reference] ?? null;

    return is_array($intent) ? $intent : null;
}

function sisonke_payfast_complete_intent(PDO $pdo, string $reference): array
{
    $intent = sisonke_payfast_get_intent($reference);
    if (!$intent) {
        return ['success' => false, 'message' => 'PayFast sandbox payment session was not found.'];
    }

    $result = sisonke_join_campaign(
        $pdo,
        (int) $intent['buyer_id'],
        (int) $intent['campaign_id'],
        (int) $intent['quantity'],
        'payfast_sandbox',
        $reference
    );

    if ($result['success']) {
        unset($_SESSION['payfast_intents'][$reference]);
    }

    return $result;
}
