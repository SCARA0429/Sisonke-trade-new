<?php

declare(strict_types=1);

require_once __DIR__ . '/marketplace_service.php';

function sisonke_payfast_endpoint(): string
{
    $mode = strtolower(trim((string) (getenv('SISONKE_PAYFAST_MODE') ?: '')));
    if ($mode === 'live' || $mode === 'production') {
        return 'https://www.payfast.co.za/eng/process';
    }
    if ($mode === 'sandbox' || $mode === 'test') {
        return 'https://sandbox.payfast.co.za/eng/process';
    }

    $configured = getenv('SISONKE_PAYFAST_ENDPOINT');
    if (is_string($configured) && $configured !== '') {
        return $configured;
    }

    return 'https://sandbox.payfast.co.za/eng/process';
}

function sisonke_payfast_is_sandbox(): bool
{
    return str_contains(strtolower(sisonke_payfast_endpoint()), 'sandbox.payfast.co.za');
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

function sisonke_payfast_public_url(string $path): string
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
    return (bool) preg_match('/^https?:\/\/(localhost|127\.0\.0\.1|.*\.local)(:|\/)/i', sisonke_payfast_public_url('pages/payfast_return.php'));
}

function sisonke_payfast_simulation_allowed(): bool
{
    return sisonke_payfast_is_sandbox() && sisonke_payfast_uses_local_urls();
}

function sisonke_payfast_validate_url(): string
{
    return sisonke_payfast_is_sandbox()
        ? 'https://sandbox.payfast.co.za/eng/query/validate'
        : 'https://www.payfast.co.za/eng/query/validate';
}

function sisonke_payfast_payment_method(): string
{
    return sisonke_payfast_is_sandbox() ? 'payfast_sandbox' : 'payfast';
}

function sisonke_payfast_gateway_label_key(): string
{
    return sisonke_payfast_is_sandbox() ? 'payfast_sandbox' : 'payfast_live';
}

function sisonke_payfast_continue_label_key(): string
{
    return sisonke_payfast_is_sandbox() ? 'continue_to_payfast' : 'continue_to_payfast_live';
}

function sisonke_payfast_checkout_ready(): array
{
    if (sisonke_payfast_is_sandbox()) {
        return ['ready' => true, 'message' => ''];
    }

    if (sisonke_payfast_passphrase() === '') {
        return [
            'ready' => false,
            'message' => 'Live PayFast requires SISONKE_PAYFAST_PASSPHRASE on the server.',
        ];
    }

    if (sisonke_payfast_merchant_id() === '10000100' || sisonke_payfast_merchant_key() === '46f0cd694581a') {
        return [
            'ready' => false,
            'message' => 'Live PayFast requires your own SISONKE_PAYFAST_MERCHANT_ID and SISONKE_PAYFAST_MERCHANT_KEY.',
        ];
    }

    if (sisonke_payfast_uses_local_urls()) {
        return [
            'ready' => false,
            'message' => 'Live PayFast requires SISONKE_PUBLIC_URL to be set to your public HTTPS domain.',
        ];
    }

    return ['ready' => true, 'message' => ''];
}

function sisonke_bootstrap_payfast_schema(PDO $pdo): void
{
    static $bootstrapped = false;
    if ($bootstrapped) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS payfast_payment_intents (
            intent_id INT AUTO_INCREMENT PRIMARY KEY,
            reference VARCHAR(100) NOT NULL,
            campaign_id INT NOT NULL,
            buyer_id INT NOT NULL,
            quantity INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending','completed','cancelled','failed') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL DEFAULT NULL,
            UNIQUE KEY reference (reference),
            INDEX (buyer_id),
            INDEX (campaign_id),
            INDEX (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $bootstrapped = true;
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
    $prefix = sisonke_payfast_is_sandbox() ? 'PF-ST-' : 'PF-';

    return $prefix . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
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

function sisonke_payfast_save_intent(PDO $pdo, array $intent): void
{
    sisonke_bootstrap_payfast_schema($pdo);

    $stmt = $pdo->prepare(
        'INSERT INTO payfast_payment_intents (reference, campaign_id, buyer_id, quantity, amount, status)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            campaign_id = VALUES(campaign_id),
            buyer_id = VALUES(buyer_id),
            quantity = VALUES(quantity),
            amount = VALUES(amount),
            status = IF(status = \'completed\', status, VALUES(status))'
    );
    $stmt->execute([
        $intent['reference'],
        (int) $intent['campaign_id'],
        (int) $intent['buyer_id'],
        (int) $intent['quantity'],
        (float) $intent['amount'],
        'pending',
    ]);
}

function sisonke_payfast_load_intent(PDO $pdo, string $reference): ?array
{
    sisonke_bootstrap_payfast_schema($pdo);

    $stmt = $pdo->prepare(
        'SELECT reference, campaign_id, buyer_id, quantity, amount, status
         FROM payfast_payment_intents
         WHERE reference = ?
         LIMIT 1'
    );
    $stmt->execute([$reference]);
    $row = $stmt->fetch();

    return is_array($row) ? $row : null;
}

function sisonke_payfast_mark_intent(PDO $pdo, string $reference, string $status): void
{
    sisonke_bootstrap_payfast_schema($pdo);

    $completedAt = $status === 'completed' ? date('Y-m-d H:i:s') : null;
    $stmt = $pdo->prepare(
        'UPDATE payfast_payment_intents
         SET status = ?, completed_at = COALESCE(?, completed_at)
         WHERE reference = ?'
    );
    $stmt->execute([$status, $completedAt, $reference]);
}

function sisonke_payfast_find_transaction(PDO $pdo, string $reference): ?array
{
    $stmt = $pdo->prepare(
        'SELECT transaction_id, participant_id, buyer_id
         FROM transactions
         WHERE reference_number = ?
         LIMIT 1'
    );
    $stmt->execute([$reference]);

    $row = $stmt->fetch();

    return is_array($row) ? $row : null;
}

function sisonke_payfast_intent_from_notification(array $payload): ?array
{
    $reference = trim((string) ($payload['m_payment_id'] ?? ''));
    $campaignId = (int) ($payload['custom_int1'] ?? 0);
    $buyerId = (int) ($payload['custom_str1'] ?? 0);
    $quantity = (int) ($payload['custom_int2'] ?? 0);
    $amount = (float) ($payload['amount_gross'] ?? $payload['amount'] ?? 0);

    if ($reference === '' || $campaignId <= 0 || $buyerId <= 0 || $quantity <= 0) {
        return null;
    }

    return [
        'reference' => $reference,
        'campaign_id' => $campaignId,
        'buyer_id' => $buyerId,
        'quantity' => $quantity,
        'amount' => $amount,
    ];
}

function sisonke_payfast_validate_itn(array $payload): bool
{
    if ($payload === []) {
        return false;
    }

    if (!function_exists('curl_init')) {
        return false;
    }

    $ch = curl_init(sisonke_payfast_validate_url());
    if ($ch === false) {
        return false;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return is_string($response) && strtoupper(trim($response)) === 'VALID';
}

function sisonke_payfast_verify_notification(array $payload): bool
{
    if ((string) ($payload['merchant_id'] ?? '') !== sisonke_payfast_merchant_id()) {
        return false;
    }

    $passphrase = sisonke_payfast_passphrase();
    $signature = trim((string) ($payload['signature'] ?? ''));

    if (!sisonke_payfast_is_sandbox() || $passphrase !== '' || $signature !== '') {
        if ($signature === '') {
            return false;
        }

        $expected = sisonke_payfast_signature($payload, $passphrase);
        if (!hash_equals($expected, $signature)) {
            return false;
        }
    }

    return sisonke_payfast_validate_itn($payload);
}

function sisonke_payfast_amount_matches(array $intent, array $payload): bool
{
    $expected = number_format((float) ($intent['amount'] ?? 0), 2, '.', '');
    $paid = number_format((float) ($payload['amount_gross'] ?? $payload['amount'] ?? 0), 2, '.', '');

    return $expected !== '0.00' && hash_equals($expected, $paid);
}

function sisonke_payfast_create_intent(PDO $pdo, array $campaign, int $buyerId, string $buyerEmail, string $buyerName, int $quantity): array
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

    sisonke_payfast_save_intent($pdo, $intent);
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
                'return_url' => sisonke_payfast_public_url('pages/payfast_return.php?ref=' . rawurlencode($reference)),
                'cancel_url' => sisonke_payfast_public_url('pages/payfast_cancel.php?ref=' . rawurlencode($reference)),
                'notify_url' => sisonke_payfast_public_url('api/payfast_notify.php'),
            ]
            + array_slice($data, 2, null, true);
    }

    $passphrase = sisonke_payfast_passphrase();
    if ($passphrase !== '' || !sisonke_payfast_is_sandbox()) {
        $data['signature'] = sisonke_payfast_signature($data, $passphrase);
    }

    return ['intent' => $intent, 'data' => $data];
}

function sisonke_payfast_get_intent(PDO $pdo, string $reference): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $sessionIntent = $_SESSION['payfast_intents'][$reference] ?? null;
    if (is_array($sessionIntent)) {
        return $sessionIntent;
    }

    return sisonke_payfast_load_intent($pdo, $reference);
}

function sisonke_payfast_fulfill_intent(PDO $pdo, array $intent, string $reference): array
{
    $existing = sisonke_payfast_find_transaction($pdo, $reference);
    if ($existing !== null) {
        sisonke_payfast_mark_intent($pdo, $reference, 'completed');
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        unset($_SESSION['payfast_intents'][$reference]);

        return [
            'success' => true,
            'message' => sisonke_t('payfast_payment_confirmed'),
            'participant_id' => (int) ($existing['participant_id'] ?? 0),
            'reference' => $reference,
        ];
    }

    $result = sisonke_join_campaign(
        $pdo,
        (int) $intent['buyer_id'],
        (int) $intent['campaign_id'],
        (int) $intent['quantity'],
        sisonke_payfast_payment_method(),
        $reference
    );

    if ($result['success']) {
        sisonke_payfast_mark_intent($pdo, $reference, 'completed');
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        unset($_SESSION['payfast_intents'][$reference]);
    }

    return $result;
}

function sisonke_payfast_process_payment(PDO $pdo, array $payload): array
{
    sisonke_bootstrap_payfast_schema($pdo);

    $status = strtoupper(trim((string) ($payload['payment_status'] ?? '')));
    if ($status !== 'COMPLETE') {
        return ['success' => false, 'message' => 'PayFast payment was not completed.'];
    }

    $reference = trim((string) ($payload['m_payment_id'] ?? ''));
    if ($reference === '') {
        return ['success' => false, 'message' => 'PayFast payment reference was missing.'];
    }

    if (!sisonke_payfast_verify_notification($payload)) {
        return ['success' => false, 'message' => 'PayFast payment could not be verified.'];
    }

    $intent = sisonke_payfast_load_intent($pdo, $reference) ?? sisonke_payfast_intent_from_notification($payload);
    if ($intent === null) {
        return ['success' => false, 'message' => 'PayFast payment intent was not found.'];
    }

    if (!sisonke_payfast_amount_matches($intent, $payload)) {
        return ['success' => false, 'message' => 'PayFast payment amount did not match the campaign total.'];
    }

    return sisonke_payfast_fulfill_intent($pdo, $intent, $reference);
}

function sisonke_payfast_complete_intent(PDO $pdo, string $reference): array
{
    $intent = sisonke_payfast_get_intent($pdo, $reference);
    if ($intent === null) {
        return ['success' => false, 'message' => sisonke_t('payfast_missing_reference')];
    }

    return sisonke_payfast_fulfill_intent($pdo, $intent, $reference);
}
