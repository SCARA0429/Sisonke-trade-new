<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/marketplace_service.php';

function seed_user(PDO $pdo, string $email, string $fullName, string $role, string $profileValue, string $permissionLevel = 'support'): int
{
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $userId = (int) ($stmt->fetchColumn() ?: 0);

    if ($userId === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO users (email, password_hash, full_name, role, is_active)
             VALUES (?, ?, ?, ?, 1)'
        );
        $stmt->execute([$email, password_hash('Password123', PASSWORD_BCRYPT), $fullName, $role]);
        $userId = (int) $pdo->lastInsertId();
    } else {
        $stmt = $pdo->prepare(
            'UPDATE users SET full_name = ?, role = ?, is_active = 1, password_hash = ? WHERE user_id = ?'
        );
        $stmt->execute([$fullName, $role, password_hash('Password123', PASSWORD_BCRYPT), $userId]);
    }

    sisonke_sync_user_profile($pdo, $userId, $role, $profileValue, $permissionLevel);

    return $userId;
}

function seed_product(PDO $pdo, int $sellerId, string $name, string $description, string $category, float $price, int $stock, ?string $imageUrl = null): int
{
    $stmt = $pdo->prepare('SELECT product_id FROM products WHERE seller_id = ? AND name = ? LIMIT 1');
    $stmt->execute([$sellerId, $name]);
    $productId = (int) ($stmt->fetchColumn() ?: 0);

    if ($productId === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO products (seller_id, name, description, category, unit_price, quantity_available, image_url, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([$sellerId, $name, $description, $category, $price, $stock, $imageUrl]);
        return (int) $pdo->lastInsertId();
    }

    $stmt = $pdo->prepare(
        'UPDATE products
         SET description = ?, category = ?, unit_price = ?, quantity_available = ?, image_url = ?, is_active = 1
         WHERE product_id = ?'
    );
    $stmt->execute([$description, $category, $price, $stock, $imageUrl, $productId]);

    return $productId;
}

function seed_campaign(PDO $pdo, int $sellerId, int $productId, float $price, int $min, int $max, int $target, string $deadline): int
{
    $stmt = $pdo->prepare('SELECT campaign_id FROM group_buy_campaigns WHERE product_id = ? AND seller_id = ? LIMIT 1');
    $stmt->execute([$productId, $sellerId]);
    $campaignId = (int) ($stmt->fetchColumn() ?: 0);
    $targetAmount = round($price * $target, 2);

    if ($campaignId === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO group_buy_campaigns
                (seller_id, product_id, campaign_price, min_participants, max_participants, target_quantity, target_amount, deadline, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$sellerId, $productId, $price, $min, $max, $target, $targetAmount, $deadline, 'active']);
        return (int) $pdo->lastInsertId();
    }

    $stmt = $pdo->prepare(
        'UPDATE group_buy_campaigns
         SET campaign_price = ?, min_participants = ?, max_participants = ?, target_quantity = ?, target_amount = ?, deadline = ?, status = ?
         WHERE campaign_id = ?'
    );
    $stmt->execute([$price, $min, $max, $target, $targetAmount, $deadline, 'active', $campaignId]);

    return $campaignId;
}

$adminId = seed_user($pdo, 'admin@sisonke.test', 'Nandi Mokoena', 'admin', '', 'super_admin');
$sellerId = seed_user($pdo, 'seller@sisonke.test', 'Thabo Dlamini', 'seller', 'Bhekizizwe Traders');
$buyerId = seed_user($pdo, 'buyer@sisonke.test', 'Lerato Nkosi', 'buyer', '321 Vilakazi Street, Soweto');

$pdo->prepare("UPDATE sellers SET verification_status = 'verified', reputation_score = 4.80 WHERE seller_id = ?")->execute([$sellerId]);

$maizeId = seed_product(
    $pdo,
    $sellerId,
    '10KG Maize Meal',
    'Premium white maize meal for household and spaza shop bulk buying.',
    'Groceries',
    105.00,
    180
);
$shoesId = seed_product(
    $pdo,
    $sellerId,
    'School Shoes',
    'Durable black school shoes for primary learners and uniform resellers.',
    'School goods',
    165.00,
    80
);
$groceryId = seed_product(
    $pdo,
    $sellerId,
    'Grocery Mix',
    'Community pantry pack with oil, sugar, tea, beans, rice, and soap.',
    'Household essentials',
    520.00,
    45
);

$deadline = date('Y-m-d H:i:s', strtotime('+21 days'));
$maizeCampaignId = seed_campaign($pdo, $sellerId, $maizeId, 89.00, 10, 120, 80, $deadline);
seed_campaign($pdo, $sellerId, $shoesId, 120.00, 8, 60, 40, date('Y-m-d H:i:s', strtotime('+14 days')));
seed_campaign($pdo, $sellerId, $groceryId, 450.00, 5, 35, 25, date('Y-m-d H:i:s', strtotime('+10 days')));

$stmt = $pdo->prepare(
    'SELECT participant_id FROM campaign_participants WHERE buyer_id = ? AND campaign_id = ? LIMIT 1'
);
$stmt->execute([$buyerId, $maizeCampaignId]);
$participantId = (int) ($stmt->fetchColumn() ?: 0);

if ($participantId === 0) {
    $result = sisonke_join_campaign($pdo, $buyerId, $maizeCampaignId, 3, 'payfast_sandbox');
    $participantId = (int) ($result['participant_id'] ?? 0);
}

if ($participantId > 0) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM disputes WHERE participant_id = ?');
    $stmt->execute([$participantId]);
    if ((int) $stmt->fetchColumn() === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO disputes (participant_id, campaign_id, buyer_id, seller_id, reason, details, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $participantId,
            $maizeCampaignId,
            $buyerId,
            $sellerId,
            'Pickup window missed',
            'Buyer could not collect during the agreed pickup window.',
            'reviewing',
        ]);
    }
}

echo "Demo data ready.\n";
echo "Admin: admin@sisonke.test / Password123\n";
echo "Seller: seller@sisonke.test / Password123\n";
echo "Buyer: buyer@sisonke.test / Password123\n";
