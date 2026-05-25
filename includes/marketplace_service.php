<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function sisonke_e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function sisonke_money(float|int|string $amount): string
{
    return 'R' . number_format((float) $amount, 2);
}

function sisonke_current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function sisonke_current_role(): string
{
    return (string) ($_SESSION['user_role'] ?? 'guest');
}

function sisonke_is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function sisonke_redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function sisonke_flash(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function sisonke_take_flashes(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return is_array($messages) ? $messages : [];
}

function sisonke_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);

    return (int) $stmt->fetchColumn() > 0;
}

function sisonke_bootstrap_marketplace_schema(PDO $pdo): void
{
    static $bootstrapped = false;
    if ($bootstrapped) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS disputes (
            dispute_id INT AUTO_INCREMENT PRIMARY KEY,
            participant_id INT NULL,
            campaign_id INT NULL,
            buyer_id INT NULL,
            seller_id INT NULL,
            reason VARCHAR(255) NOT NULL,
            details TEXT NULL,
            status ENUM('open','reviewing','resolved','rejected') DEFAULT 'open',
            resolution_note TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolved_at TIMESTAMP NULL DEFAULT NULL,
            INDEX (participant_id),
            INDEX (campaign_id),
            INDEX (buyer_id),
            INDEX (seller_id),
            CONSTRAINT disputes_participant_fk FOREIGN KEY (participant_id)
                REFERENCES campaign_participants (participant_id) ON DELETE SET NULL,
            CONSTRAINT disputes_campaign_fk FOREIGN KEY (campaign_id)
                REFERENCES group_buy_campaigns (campaign_id) ON DELETE SET NULL,
            CONSTRAINT disputes_buyer_fk FOREIGN KEY (buyer_id)
                REFERENCES buyers (buyer_id) ON DELETE SET NULL,
            CONSTRAINT disputes_seller_fk FOREIGN KEY (seller_id)
                REFERENCES sellers (seller_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    if (!sisonke_column_exists($pdo, 'transactions', 'participant_id')) {
        $pdo->exec('ALTER TABLE transactions ADD COLUMN participant_id INT NULL AFTER escrow_id');
        $pdo->exec('ALTER TABLE transactions ADD INDEX participant_id (participant_id)');
    }

    if (!sisonke_column_exists($pdo, 'group_buy_campaigns', 'image_url')) {
        $pdo->exec('ALTER TABLE group_buy_campaigns ADD COLUMN image_url VARCHAR(255) DEFAULT NULL AFTER target_amount');
    }

    $bootstrapped = true;
}

function sisonke_admin_permissions(PDO $pdo, int $adminId): array
{
    $stmt = $pdo->prepare(
        'SELECT permission_level, can_resolve_disputes, can_manage_users
         FROM admins WHERE admin_id = ? LIMIT 1'
    );
    $stmt->execute([$adminId]);
    $row = $stmt->fetch();

    if (!$row) {
        return [
            'permission_level' => 'super_admin',
            'can_resolve_disputes' => true,
            'can_manage_users' => true,
        ];
    }

    return [
        'permission_level' => (string) $row['permission_level'],
        'can_resolve_disputes' => (bool) $row['can_resolve_disputes'],
        'can_manage_users' => (bool) $row['can_manage_users'],
    ];
}

function sisonke_require_admin_capability(PDO $pdo, string $capability): void
{
    $adminId = sisonke_current_user_id();
    if ($adminId === null) {
        sisonke_redirect(SISONKE_BASE_URL . '/pages/login.php');
    }

    $permissions = sisonke_admin_permissions($pdo, $adminId);
    if (empty($permissions[$capability])) {
        http_response_code(403);
        require dirname(__DIR__) . '/includes/header.php';
        echo '<div class="container py-5"><div class="alert alert-warning">Your admin role cannot perform this action.</div></div>';
        require dirname(__DIR__) . '/includes/footer.php';
        exit;
    }
}

function sisonke_campaign_visual_key(array $campaign): string
{
    $category = strtolower((string) ($campaign['category'] ?? ''));

    if (str_contains($category, 'school')) {
        return 'shoes';
    }
    if (str_contains($category, 'household') || str_contains($category, 'grocery')) {
        return 'grocery';
    }
    if (str_contains($category, 'grocer')) {
        return 'maize';
    }

    return 'grocery';
}

function sisonke_default_product_image_url(string $visualKey): string
{
    $base = SISONKE_BASE_URL;
    $defaults = [
        'maize' => $base . '/assets/images/products/maize.png',
        'shoes' => $base . '/assets/images/products/shoes.png',
        'grocery' => $base . '/assets/images/products/grocery.png',
    ];

    return $defaults[$visualKey] ?? $defaults['grocery'];
}

function sisonke_campaign_image_url(array $campaign): string
{
    $uploaded = trim((string) ($campaign['image_url'] ?? ''));
    if ($uploaded !== '') {
        return $uploaded;
    }

    return sisonke_default_product_image_url(sisonke_campaign_visual_key($campaign));
}

function sisonke_campaign_progress(array $campaign): int
{
    $target = max(1, (int) ($campaign['target_quantity'] ?? 1));
    $current = max(0, (int) ($campaign['current_quantity'] ?? 0));

    return min(100, (int) round(($current / $target) * 100));
}

function sisonke_fetch_campaigns(PDO $pdo, string $search = '', int $limit = 0, ?int $sellerId = null): array
{
    sisonke_bootstrap_marketplace_schema($pdo);

    $where = ['p.is_active = 1'];
    $params = [];

    if ($search !== '') {
        $where[] = '(p.name LIKE ? OR p.description LIKE ? OR p.category LIKE ? OR s.business_name LIKE ?)';
        $needle = '%' . $search . '%';
        array_push($params, $needle, $needle, $needle, $needle);
    }

    if ($sellerId !== null) {
        $where[] = 'c.seller_id = ?';
        $params[] = $sellerId;
    }

    $limitSql = $limit > 0 ? ' LIMIT ' . $limit : '';
    $sql = "
        SELECT
            c.campaign_id,
            c.seller_id,
            c.product_id,
            c.campaign_price,
            c.min_participants,
            c.max_participants,
            c.target_quantity,
            c.target_amount,
            c.image_url AS campaign_image_url,
            c.current_quantity,
            c.deadline,
            c.status,
            c.created_at,
            p.name AS product_name,
            p.description,
            p.category,
            p.unit_price,
            p.quantity_available,
            COALESCE(c.image_url, p.image_url) AS image_url,
            p.image_url AS product_image_url,
            s.business_name,
            s.verification_status,
            s.reputation_score,
            u.full_name AS seller_name,
            COUNT(DISTINCT cp.participant_id) AS participant_count,
            COALESCE(SUM(cp.quantity), 0) AS joined_quantity
        FROM group_buy_campaigns c
        INNER JOIN products p ON p.product_id = c.product_id
        INNER JOIN sellers s ON s.seller_id = c.seller_id
        INNER JOIN users u ON u.user_id = s.seller_id
        LEFT JOIN campaign_participants cp ON cp.campaign_id = c.campaign_id
        WHERE " . implode(' AND ', $where) . "
        GROUP BY c.campaign_id
        ORDER BY FIELD(c.status, 'active', 'fulfilled', 'closed', 'cancelled'), c.deadline ASC
        {$limitSql}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function sisonke_fetch_campaign(PDO $pdo, int $campaignId): ?array
{
    $campaigns = sisonke_fetch_campaigns($pdo, '', 0, null);
    foreach ($campaigns as $campaign) {
        if ((int) $campaign['campaign_id'] === $campaignId) {
            return $campaign;
        }
    }

    return null;
}

function sisonke_fetch_buyer_orders(PDO $pdo, int $buyerId): array
{
    sisonke_bootstrap_marketplace_schema($pdo);

    $stmt = $pdo->prepare(
        "SELECT
            cp.participant_id,
            cp.quantity,
            cp.amount_paid,
            cp.has_confirmed_delivery,
            cp.joined_at,
            cp.confirmed_at,
            c.campaign_id,
            c.status AS campaign_status,
            c.deadline,
            p.name AS product_name,
            p.category,
            s.business_name,
            t.reference_number,
            t.status AS transaction_status,
            e.status AS escrow_status
         FROM campaign_participants cp
         INNER JOIN group_buy_campaigns c ON c.campaign_id = cp.campaign_id
         INNER JOIN products p ON p.product_id = c.product_id
         INNER JOIN sellers s ON s.seller_id = c.seller_id
         LEFT JOIN transactions t ON t.participant_id = cp.participant_id
         LEFT JOIN escrow_payments e ON e.escrow_id = t.escrow_id
         WHERE cp.buyer_id = ?
         ORDER BY cp.joined_at DESC"
    );
    $stmt->execute([$buyerId]);

    return $stmt->fetchAll();
}

function sisonke_join_campaign(PDO $pdo, int $buyerId, int $campaignId, int $quantity, string $paymentMethod, string $externalReference = ''): array
{
    sisonke_bootstrap_marketplace_schema($pdo);

    $quantity = max(1, min(50, $quantity));
    $paymentMethod = in_array($paymentMethod, ['payfast', 'payfast_sandbox', 'ozow_eft', 'card_3ds', 'cash_pickup_demo'], true)
        ? $paymentMethod
        : 'payfast';

    $reference = $externalReference !== ''
        ? $externalReference
        : 'ST-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

    try {
        if ($externalReference !== '') {
            $stmt = $pdo->prepare('SELECT transaction_id, participant_id FROM transactions WHERE reference_number = ? LIMIT 1');
            $stmt->execute([$reference]);
            $existing = $stmt->fetch();
            if (is_array($existing)) {
                return [
                    'success' => true,
                    'message' => 'Campaign already joined for this payment reference.',
                    'participant_id' => (int) ($existing['participant_id'] ?? 0),
                    'reference' => $reference,
                ];
            }
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "SELECT c.*, p.name, p.quantity_available
             FROM group_buy_campaigns c
             INNER JOIN products p ON p.product_id = c.product_id
             WHERE c.campaign_id = ?
             FOR UPDATE"
        );
        $stmt->execute([$campaignId]);
        $campaign = $stmt->fetch();

        if (!$campaign) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Campaign not found.'];
        }

        if ((string) $campaign['status'] !== 'active') {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'This campaign is not accepting new buyers.'];
        }

        if (strtotime((string) $campaign['deadline']) < time()) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'This campaign has already closed.'];
        }

        $newQuantity = (int) $campaign['current_quantity'] + $quantity;
        if ($newQuantity > (int) $campaign['max_participants']) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'The campaign is already full.'];
        }

        $amount = round((float) $campaign['campaign_price'] * $quantity, 2);

        $stmt = $pdo->prepare(
            'INSERT INTO escrow_payments (campaign_id, total_amount, status, confirmations_required)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$campaignId, $amount, 'held', 1]);
        $escrowId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            'INSERT INTO campaign_participants (campaign_id, buyer_id, quantity, amount_paid)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$campaignId, $buyerId, $quantity, $amount]);
        $participantId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            'INSERT INTO transactions
                (escrow_id, participant_id, buyer_id, seller_id, amount, payment_method, status, reference_number)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $escrowId,
            $participantId,
            $buyerId,
            (int) $campaign['seller_id'],
            $amount,
            $paymentMethod,
            'completed',
            $reference,
        ]);

        $nextStatus = $newQuantity >= (int) $campaign['target_quantity'] ? 'fulfilled' : 'active';
        $stmt = $pdo->prepare(
            'UPDATE group_buy_campaigns SET current_quantity = ?, status = ? WHERE campaign_id = ?'
        );
        $stmt->execute([$newQuantity, $nextStatus, $campaignId]);

        $stmt = $pdo->prepare('UPDATE buyers SET total_purchases = total_purchases + 1 WHERE buyer_id = ?');
        $stmt->execute([$buyerId]);

        $pdo->commit();

        $successMessage = match ($paymentMethod) {
            'payfast' => 'Campaign joined. Your PayFast payment is held in escrow.',
            'payfast_sandbox' => 'Campaign joined. Your PayFast sandbox payment is held in escrow.',
            default => 'Campaign joined. Your payment is held in escrow.',
        };

        return [
            'success' => true,
            'message' => $successMessage,
            'participant_id' => $participantId,
            'reference' => $reference,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['success' => false, 'message' => 'Could not join the campaign. Please try again.'];
    }
}

function sisonke_confirm_delivery(PDO $pdo, int $buyerId, int $participantId): array
{
    sisonke_bootstrap_marketplace_schema($pdo);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "SELECT
                cp.participant_id,
                cp.quantity,
                cp.has_confirmed_delivery,
                cp.campaign_id,
                c.seller_id,
                t.transaction_id,
                t.escrow_id
             FROM campaign_participants cp
             INNER JOIN group_buy_campaigns c ON c.campaign_id = cp.campaign_id
             LEFT JOIN transactions t ON t.participant_id = cp.participant_id
             WHERE cp.participant_id = ? AND cp.buyer_id = ?
             FOR UPDATE"
        );
        $stmt->execute([$participantId, $buyerId]);
        $order = $stmt->fetch();

        if (!$order) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Order not found.'];
        }

        if ((bool) $order['has_confirmed_delivery']) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Delivery was already confirmed.'];
        }

        $stmt = $pdo->prepare(
            'UPDATE campaign_participants
             SET has_confirmed_delivery = 1, confirmed_at = NOW()
             WHERE participant_id = ?'
        );
        $stmt->execute([$participantId]);

        if (!empty($order['escrow_id'])) {
            $stmt = $pdo->prepare(
                "UPDATE escrow_payments
                 SET status = 'released', confirmations_received = confirmations_required, released_at = NOW()
                 WHERE escrow_id = ?"
            );
            $stmt->execute([(int) $order['escrow_id']]);
        }

        $stmt = $pdo->prepare('UPDATE buyers SET total_confirmations = total_confirmations + 1 WHERE buyer_id = ?');
        $stmt->execute([$buyerId]);

        $stmt = $pdo->prepare('UPDATE sellers SET total_sales = total_sales + ? WHERE seller_id = ?');
        $stmt->execute([(int) $order['quantity'], (int) $order['seller_id']]);

        $pdo->commit();

        return ['success' => true, 'message' => 'Delivery confirmed. Escrow has been released to the seller.'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['success' => false, 'message' => 'Could not confirm delivery. Please try again.'];
    }
}

function sisonke_fetch_seller_products(PDO $pdo, int $sellerId): array
{
    $stmt = $pdo->prepare(
        'SELECT p.*,
            COUNT(c.campaign_id) AS campaign_count,
            COALESCE(SUM(c.current_quantity), 0) AS committed_quantity
         FROM products p
         LEFT JOIN group_buy_campaigns c ON c.product_id = p.product_id
         WHERE p.seller_id = ?
         GROUP BY p.product_id
         ORDER BY p.created_at DESC'
    );
    $stmt->execute([$sellerId]);

    return $stmt->fetchAll();
}

function sisonke_fetch_seller_summary(PDO $pdo, int $sellerId): array
{
    $stmt = $pdo->prepare(
        "SELECT
            (SELECT COUNT(*) FROM products WHERE seller_id = ?) AS products_count,
            (SELECT COUNT(*) FROM products WHERE seller_id = ? AND is_active = 1) AS active_products_count,
            (SELECT COUNT(*) FROM group_buy_campaigns WHERE seller_id = ?) AS campaigns_count,
            (SELECT COUNT(*) FROM group_buy_campaigns WHERE seller_id = ? AND status IN ('active','fulfilled')) AS active_campaigns,
            (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE seller_id = ? AND status = 'completed') AS revenue,
            (SELECT COUNT(*) FROM disputes WHERE seller_id = ? AND status IN ('open','reviewing')) AS open_disputes"
    );
    $stmt->execute([$sellerId, $sellerId, $sellerId, $sellerId, $sellerId, $sellerId]);

    return $stmt->fetch() ?: [
        'products_count' => 0,
        'active_products_count' => 0,
        'campaigns_count' => 0,
        'active_campaigns' => 0,
        'revenue' => 0,
        'open_disputes' => 0,
    ];
}

function sisonke_create_product(PDO $pdo, int $sellerId, array $data): array
{
    $name = trim((string) ($data['name'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $category = trim((string) ($data['category'] ?? ''));
    $price = (float) ($data['unit_price'] ?? 0);
    $quantity = (int) ($data['quantity_available'] ?? 0);
    $imageUrl = trim((string) ($data['image_url'] ?? ''));

    if ($name === '' || strlen($name) > 150) {
        return ['success' => false, 'message' => 'Product name is required and must be under 150 characters.'];
    }
    if ($description === '') {
        return ['success' => false, 'message' => 'Product description is required.'];
    }
    if ($category === '' || strlen($category) > 50) {
        return ['success' => false, 'message' => 'Category is required and must be under 50 characters.'];
    }
    if ($price <= 0 || $quantity <= 0) {
        return ['success' => false, 'message' => 'Price and stock quantity must be greater than zero.'];
    }

    $stmt = $pdo->prepare(
        'INSERT INTO products (seller_id, name, description, category, unit_price, quantity_available, image_url)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$sellerId, $name, $description, $category, $price, $quantity, $imageUrl !== '' ? $imageUrl : null]);

    return [
        'success' => true,
        'message' => 'Product saved.',
        'product_id' => (int) $pdo->lastInsertId(),
    ];
}

function sisonke_campaign_form_state(PDO $pdo, int $sellerId, int $preselectProductId = 0): array
{
    $allProducts = sisonke_fetch_seller_products($pdo, $sellerId);
    $products = array_values(array_filter(
        $allProducts,
        static fn (array $product): bool => (bool) $product['is_active']
    ));
    $productIds = array_map(static fn (array $product): int => (int) $product['product_id'], $products);

    if ($preselectProductId <= 0 || !in_array($preselectProductId, $productIds, true)) {
        $preselectProductId = $productIds[0] ?? 0;
    }

    $prefillCampaignPrice = '';
    $selectedProduct = null;
    foreach ($products as $product) {
        if ((int) $product['product_id'] === $preselectProductId) {
            $prefillCampaignPrice = number_format((float) $product['unit_price'], 2, '.', '');
            $selectedProduct = $product;
            break;
        }
    }

    return [
        'products' => $products,
        'preselect_product_id' => $preselectProductId,
        'prefill_campaign_price' => $prefillCampaignPrice,
        'selected_product' => $selectedProduct,
    ];
}

function sisonke_store_campaign_image(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => null];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Campaign image could not be uploaded. Please try again.'];
    }

    if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Campaign image must be 5MB or smaller.'];
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    $imageInfo = $tmpName !== '' ? @getimagesize($tmpName) : false;
    $mimeType = is_array($imageInfo) ? (string) ($imageInfo['mime'] ?? '') : '';
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (!isset($extensions[$mimeType])) {
        return ['success' => false, 'message' => 'Campaign image must be a JPG, PNG, WebP, or GIF file.'];
    }

    $uploadDir = dirname(__DIR__) . '/assets/uploads/campaigns';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        return ['success' => false, 'message' => 'Campaign image folder could not be created.'];
    }

    $filename = 'campaign_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extensions[$mimeType];
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($tmpName, $destination)) {
        return ['success' => false, 'message' => 'Campaign image could not be saved.'];
    }

    return [
        'success' => true,
        'path' => SISONKE_BASE_URL . '/assets/uploads/campaigns/' . $filename,
    ];
}

function sisonke_validate_campaign_image_url(string $imageUrl): array
{
    $imageUrl = trim($imageUrl);
    if ($imageUrl === '') {
        return ['success' => true, 'path' => null];
    }

    if (strlen($imageUrl) > 255 || !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        return ['success' => false, 'message' => 'Enter a valid campaign image URL.'];
    }

    $scheme = strtolower((string) parse_url($imageUrl, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        return ['success' => false, 'message' => 'Campaign image URL must start with http:// or https://.'];
    }

    $path = strtolower((string) parse_url($imageUrl, PHP_URL_PATH));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $extension = pathinfo($path, PATHINFO_EXTENSION);
    if ($extension !== '' && !in_array($extension, $allowedExtensions, true)) {
        return ['success' => false, 'message' => 'Campaign image URL must point to a JPG, PNG, WebP, or GIF image.'];
    }

    return ['success' => true, 'path' => $imageUrl];
}

function sisonke_create_campaign(PDO $pdo, int $sellerId, array $data, array $files = []): array
{
    $productId = (int) ($data['product_id'] ?? 0);
    $campaignPrice = (float) ($data['campaign_price'] ?? 0);
    $minParticipants = max(1, (int) ($data['min_participants'] ?? 1));
    $maxParticipants = max($minParticipants, (int) ($data['max_participants'] ?? $minParticipants));
    $targetQuantity = max(1, (int) ($data['target_quantity'] ?? 1));
    $deadline = trim((string) ($data['deadline'] ?? ''));

    if ($productId <= 0 || $campaignPrice <= 0 || $deadline === '') {
        return ['success' => false, 'message' => 'Product, price, and deadline are required.'];
    }

    if (strtotime($deadline) <= time()) {
        return ['success' => false, 'message' => 'Deadline must be in the future.'];
    }

    $stmt = $pdo->prepare('SELECT product_id, quantity_available FROM products WHERE product_id = ? AND seller_id = ? AND is_active = 1');
    $stmt->execute([$productId, $sellerId]);
    $product = $stmt->fetch();
    if (!$product) {
        return ['success' => false, 'message' => 'Choose one of your active products.'];
    }

    if ($targetQuantity > (int) $product['quantity_available']) {
        return ['success' => false, 'message' => 'Target quantity cannot exceed available stock.'];
    }

    $imageUpload = sisonke_store_campaign_image($files['campaign_image'] ?? []);
    if (empty($imageUpload['success'])) {
        return ['success' => false, 'message' => $imageUpload['message'] ?? 'Campaign image could not be uploaded.'];
    }

    $imageUrl = $imageUpload['path'];
    if ($imageUrl === null) {
        $urlResult = sisonke_validate_campaign_image_url((string) ($data['campaign_image_url'] ?? ''));
        if (empty($urlResult['success'])) {
            return ['success' => false, 'message' => $urlResult['message'] ?? 'Enter a valid campaign image URL.'];
        }
        $imageUrl = $urlResult['path'];
    }

    $targetAmount = round($campaignPrice * $targetQuantity, 2);
    $stmt = $pdo->prepare(
        'INSERT INTO group_buy_campaigns
            (seller_id, product_id, campaign_price, min_participants, max_participants, target_quantity, target_amount, image_url, deadline)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $sellerId,
        $productId,
        $campaignPrice,
        $minParticipants,
        $maxParticipants,
        $targetQuantity,
        $targetAmount,
        $imageUrl,
        date('Y-m-d H:i:s', strtotime($deadline)),
    ]);

    return ['success' => true, 'message' => 'Campaign created.'];
}

function sisonke_fetch_admin_summary(PDO $pdo): array
{
    sisonke_bootstrap_marketplace_schema($pdo);

    $stmt = $pdo->query(
        "SELECT
            (SELECT COUNT(*) FROM users) AS total_users,
            (SELECT COUNT(*) FROM group_buy_campaigns WHERE status IN ('active','fulfilled')) AS live_campaigns,
            (SELECT COALESCE(SUM(total_amount), 0) FROM escrow_payments WHERE status = 'held') AS escrow_held,
            (SELECT COUNT(*) FROM disputes WHERE status IN ('open','reviewing')) AS open_disputes,
            (SELECT COUNT(*) FROM transactions WHERE status = 'completed') AS completed_transactions"
    );

    return $stmt->fetch() ?: [];
}

function sisonke_fetch_admin_users(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT
            u.user_id,
            u.email,
            u.full_name,
            u.role,
            u.is_active,
            u.created_at,
            b.delivery_address,
            s.business_name,
            s.verification_status,
            a.permission_level,
            a.can_resolve_disputes,
            a.can_manage_users
         FROM users u
         LEFT JOIN buyers b ON b.buyer_id = u.user_id
         LEFT JOIN sellers s ON s.seller_id = u.user_id
         LEFT JOIN admins a ON a.admin_id = u.user_id
         ORDER BY u.created_at DESC"
    );

    return $stmt->fetchAll();
}

function sisonke_sync_user_profile(PDO $pdo, int $userId, string $role, string $profileValue, string $permissionLevel = 'support'): void
{
    if ($role === 'buyer') {
        $pdo->prepare('DELETE FROM sellers WHERE seller_id = ?')->execute([$userId]);
        $pdo->prepare('DELETE FROM admins WHERE admin_id = ?')->execute([$userId]);
        $stmt = $pdo->prepare(
            'INSERT INTO buyers (buyer_id, delivery_address)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE delivery_address = VALUES(delivery_address)'
        );
        $stmt->execute([$userId, $profileValue !== '' ? $profileValue : 'Collection point to be confirmed']);
        return;
    }

    if ($role === 'seller') {
        $pdo->prepare('DELETE FROM buyers WHERE buyer_id = ?')->execute([$userId]);
        $pdo->prepare('DELETE FROM admins WHERE admin_id = ?')->execute([$userId]);
        $stmt = $pdo->prepare(
            'INSERT INTO sellers (seller_id, business_name, verification_status)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE business_name = VALUES(business_name)'
        );
        $stmt->execute([$userId, $profileValue !== '' ? $profileValue : 'Community Seller', 'pending']);
        return;
    }

    $pdo->prepare('DELETE FROM buyers WHERE buyer_id = ?')->execute([$userId]);
    $pdo->prepare('DELETE FROM sellers WHERE seller_id = ?')->execute([$userId]);
    $permissionLevel = in_array($permissionLevel, ['super_admin', 'moderator', 'support'], true)
        ? $permissionLevel
        : 'support';
    $canResolve = in_array($permissionLevel, ['super_admin', 'moderator'], true) ? 1 : 0;
    $canManage = $permissionLevel === 'super_admin' ? 1 : 0;
    $stmt = $pdo->prepare(
        'INSERT INTO admins (admin_id, permission_level, can_resolve_disputes, can_manage_users)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            permission_level = VALUES(permission_level),
            can_resolve_disputes = VALUES(can_resolve_disputes),
            can_manage_users = VALUES(can_manage_users)'
    );
    $stmt->execute([$userId, $permissionLevel, $canResolve, $canManage]);
}

function sisonke_save_admin_user(PDO $pdo, ?int $userId, array $data): array
{
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $fullName = trim((string) ($data['full_name'] ?? ''));
    $role = strtolower(trim((string) ($data['role'] ?? 'buyer')));
    $profileValue = trim((string) ($data['profile_value'] ?? ''));
    $permissionLevel = trim((string) ($data['permission_level'] ?? 'support'));
    $password = (string) ($data['password'] ?? '');
    $isActive = isset($data['is_active']) ? 1 : 0;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Enter a valid email address.'];
    }
    if ($fullName === '' || strlen($fullName) > 120) {
        return ['success' => false, 'message' => 'Enter a full name under 120 characters.'];
    }
    if (!in_array($role, ['buyer', 'seller', 'admin'], true)) {
        return ['success' => false, 'message' => 'Choose buyer, seller, or admin.'];
    }
    if ($userId === null && strlen($password) < 6) {
        return ['success' => false, 'message' => 'New users need a password of at least 6 characters.'];
    }

    try {
        $pdo->beginTransaction();

        if ($userId === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO users (email, password_hash, full_name, role, is_active)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$email, password_hash($password, PASSWORD_BCRYPT), $fullName, $role, $isActive]);
            $userId = (int) $pdo->lastInsertId();
        } else {
            $params = [$email, $fullName, $role, $isActive, $userId];
            $passwordSql = '';
            if ($password !== '') {
                if (strlen($password) < 6) {
                    $pdo->rollBack();
                    return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
                }
                $passwordSql = ', password_hash = ?';
                $params = [$email, $fullName, $role, $isActive, password_hash($password, PASSWORD_BCRYPT), $userId];
            }

            $stmt = $pdo->prepare(
                "UPDATE users SET email = ?, full_name = ?, role = ?, is_active = ? {$passwordSql} WHERE user_id = ?"
            );
            $stmt->execute($params);
        }

        sisonke_sync_user_profile($pdo, $userId, $role, $profileValue, $permissionLevel);
        $pdo->commit();

        return ['success' => true, 'message' => 'User saved.'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['success' => false, 'message' => 'User could not be saved. Check that the email is unique.'];
    }
}

function sisonke_fetch_admin_transactions(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT
            t.transaction_id,
            t.escrow_id,
            t.participant_id,
            t.reference_number,
            t.amount,
            t.payment_method,
            t.status AS transaction_status,
            t.created_at,
            e.status AS escrow_status,
            e.released_at,
            buyer.full_name AS buyer_name,
            seller_user.full_name AS seller_name,
            s.business_name,
            p.name AS product_name
         FROM transactions t
         INNER JOIN escrow_payments e ON e.escrow_id = t.escrow_id
         INNER JOIN users buyer ON buyer.user_id = t.buyer_id
         INNER JOIN sellers s ON s.seller_id = t.seller_id
         INNER JOIN users seller_user ON seller_user.user_id = s.seller_id
         LEFT JOIN campaign_participants cp ON cp.participant_id = t.participant_id
         LEFT JOIN group_buy_campaigns c ON c.campaign_id = cp.campaign_id
         LEFT JOIN products p ON p.product_id = c.product_id
         ORDER BY t.created_at DESC"
    );

    return $stmt->fetchAll();
}

function sisonke_fetch_admin_disputes(PDO $pdo): array
{
    sisonke_bootstrap_marketplace_schema($pdo);

    $stmt = $pdo->query(
        "SELECT
            d.*,
            buyer.full_name AS buyer_name,
            seller_user.full_name AS seller_name,
            s.business_name,
            p.name AS product_name,
            t.reference_number
         FROM disputes d
         LEFT JOIN users buyer ON buyer.user_id = d.buyer_id
         LEFT JOIN sellers s ON s.seller_id = d.seller_id
         LEFT JOIN users seller_user ON seller_user.user_id = s.seller_id
         LEFT JOIN group_buy_campaigns c ON c.campaign_id = d.campaign_id
         LEFT JOIN products p ON p.product_id = c.product_id
         LEFT JOIN transactions t ON t.participant_id = d.participant_id
         ORDER BY FIELD(d.status, 'open', 'reviewing', 'resolved', 'rejected'), d.created_at DESC"
    );

    return $stmt->fetchAll();
}

sisonke_bootstrap_marketplace_schema($pdo);
