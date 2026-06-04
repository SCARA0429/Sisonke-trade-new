<?php

declare(strict_types=1);

require_once __DIR__ . '/marketplace_service.php';

function sisonke_bootstrap_messaging_schema(PDO $pdo): void
{
    static $bootstrapped = false;
    if ($bootstrapped) {
        return;
    }

    sisonke_bootstrap_marketplace_schema($pdo);

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS campaign_conversations (
            conversation_id INT AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT NOT NULL,
            buyer_id INT NOT NULL,
            seller_id INT NOT NULL,
            participant_id INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_campaign_buyer (campaign_id, buyer_id),
            INDEX (seller_id),
            INDEX (buyer_id),
            CONSTRAINT campaign_conversations_campaign_fk FOREIGN KEY (campaign_id)
                REFERENCES group_buy_campaigns (campaign_id) ON DELETE CASCADE,
            CONSTRAINT campaign_conversations_buyer_fk FOREIGN KEY (buyer_id)
                REFERENCES buyers (buyer_id) ON DELETE CASCADE,
            CONSTRAINT campaign_conversations_seller_fk FOREIGN KEY (seller_id)
                REFERENCES sellers (seller_id) ON DELETE CASCADE,
            CONSTRAINT campaign_conversations_participant_fk FOREIGN KEY (participant_id)
                REFERENCES campaign_participants (participant_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS campaign_messages (
            message_id INT AUTO_INCREMENT PRIMARY KEY,
            conversation_id INT NOT NULL,
            sender_id INT NOT NULL,
            body TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (conversation_id),
            INDEX (sender_id),
            CONSTRAINT campaign_messages_conversation_fk FOREIGN KEY (conversation_id)
                REFERENCES campaign_conversations (conversation_id) ON DELETE CASCADE,
            CONSTRAINT campaign_messages_sender_fk FOREIGN KEY (sender_id)
                REFERENCES users (user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $bootstrapped = true;
}

function sisonke_touch_conversation_after_purchase(PDO $pdo, int $campaignId, int $buyerId): void
{
    sisonke_bootstrap_messaging_schema($pdo);

    $stmt = $pdo->prepare(
        'SELECT conversation_id
         FROM campaign_conversations
         WHERE campaign_id = ? AND buyer_id = ?
         LIMIT 1'
    );
    $stmt->execute([$campaignId, $buyerId]);
    $conversationId = $stmt->fetchColumn();
    if ($conversationId === false) {
        return;
    }

    sisonke_sync_conversation_participant($pdo, (int) $conversationId);
}

function sisonke_sync_conversation_participant(PDO $pdo, int $conversationId): void
{
    $stmt = $pdo->prepare(
        'SELECT cc.buyer_id, cc.campaign_id
         FROM campaign_conversations cc
         WHERE cc.conversation_id = ?
         LIMIT 1'
    );
    $stmt->execute([$conversationId]);
    $row = $stmt->fetch();
    if (!$row) {
        return;
    }

    $participantStmt = $pdo->prepare(
        'SELECT participant_id
         FROM campaign_participants
         WHERE buyer_id = ? AND campaign_id = ?
         ORDER BY participant_id DESC
         LIMIT 1'
    );
    $participantStmt->execute([(int) $row['buyer_id'], (int) $row['campaign_id']]);
    $participantId = $participantStmt->fetchColumn();
    if ($participantId === false) {
        return;
    }

    $update = $pdo->prepare(
        'UPDATE campaign_conversations
         SET participant_id = ?
         WHERE conversation_id = ? AND (participant_id IS NULL OR participant_id <> ?)'
    );
    $update->execute([(int) $participantId, $conversationId, (int) $participantId]);
}

function sisonke_get_or_create_conversation(PDO $pdo, int $campaignId, int $buyerId): array
{
    sisonke_bootstrap_messaging_schema($pdo);

    $campaign = sisonke_fetch_campaign($pdo, $campaignId);
    if (!$campaign) {
        return ['success' => false, 'message' => 'Campaign not found.'];
    }

    $sellerId = (int) $campaign['seller_id'];
    if ($buyerId === $sellerId) {
        return ['success' => false, 'message' => 'You cannot message yourself about your own campaign.'];
    }

    $stmt = $pdo->prepare(
        'SELECT conversation_id
         FROM campaign_conversations
         WHERE campaign_id = ? AND buyer_id = ?
         LIMIT 1'
    );
    $stmt->execute([$campaignId, $buyerId]);
    $conversationId = $stmt->fetchColumn();

    if ($conversationId === false) {
        $insert = $pdo->prepare(
            'INSERT INTO campaign_conversations (campaign_id, buyer_id, seller_id)
             VALUES (?, ?, ?)'
        );
        $insert->execute([$campaignId, $buyerId, $sellerId]);
        $conversationId = (int) $pdo->lastInsertId();
    } else {
        $conversationId = (int) $conversationId;
    }

    sisonke_sync_conversation_participant($pdo, $conversationId);

    return [
        'success' => true,
        'conversation_id' => $conversationId,
        'campaign' => $campaign,
    ];
}

function sisonke_fetch_conversation(PDO $pdo, int $conversationId): ?array
{
    sisonke_bootstrap_messaging_schema($pdo);

    $stmt = $pdo->prepare(
        "SELECT
            cc.conversation_id,
            cc.campaign_id,
            cc.buyer_id,
            cc.seller_id,
            cc.participant_id,
            cc.created_at,
            cc.updated_at,
            c.status AS campaign_status,
            c.deadline,
            p.name AS product_name,
            s.business_name,
            buyer_user.full_name AS buyer_name,
            seller_user.full_name AS seller_name
         FROM campaign_conversations cc
         INNER JOIN group_buy_campaigns c ON c.campaign_id = cc.campaign_id
         INNER JOIN products p ON p.product_id = c.product_id
         INNER JOIN sellers s ON s.seller_id = cc.seller_id
         INNER JOIN users buyer_user ON buyer_user.user_id = cc.buyer_id
         INNER JOIN users seller_user ON seller_user.user_id = cc.seller_id
         WHERE cc.conversation_id = ?
         LIMIT 1"
    );
    $stmt->execute([$conversationId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function sisonke_user_can_access_conversation(array $conversation, int $userId): bool
{
    return $userId === (int) $conversation['buyer_id'] || $userId === (int) $conversation['seller_id'];
}

function sisonke_fetch_conversation_messages(PDO $pdo, int $conversationId): array
{
    sisonke_bootstrap_messaging_schema($pdo);

    $stmt = $pdo->prepare(
        "SELECT
            m.message_id,
            m.conversation_id,
            m.sender_id,
            m.body,
            m.created_at,
            u.full_name AS sender_name
         FROM campaign_messages m
         INNER JOIN users u ON u.user_id = m.sender_id
         WHERE m.conversation_id = ?
         ORDER BY m.created_at ASC, m.message_id ASC"
    );
    $stmt->execute([$conversationId]);

    return $stmt->fetchAll();
}

function sisonke_send_campaign_message(PDO $pdo, int $conversationId, int $senderId, string $body): array
{
    sisonke_bootstrap_messaging_schema($pdo);

    $conversation = sisonke_fetch_conversation($pdo, $conversationId);
    if (!$conversation) {
        return ['success' => false, 'message' => 'Conversation not found.'];
    }

    if (!sisonke_user_can_access_conversation($conversation, $senderId)) {
        return ['success' => false, 'message' => 'You do not have access to this conversation.'];
    }

    $body = trim($body);
    if ($body === '') {
        return ['success' => false, 'message' => 'Please enter a message.'];
    }

    if (strlen($body) > 2000) {
        return ['success' => false, 'message' => 'Message must be 2000 characters or fewer.'];
    }

    sisonke_sync_conversation_participant($pdo, $conversationId);

    $stmt = $pdo->prepare(
        'INSERT INTO campaign_messages (conversation_id, sender_id, body)
         VALUES (?, ?, ?)'
    );
    $stmt->execute([$conversationId, $senderId, $body]);

    $touch = $pdo->prepare('UPDATE campaign_conversations SET updated_at = CURRENT_TIMESTAMP WHERE conversation_id = ?');
    $touch->execute([$conversationId]);

    return ['success' => true, 'message' => 'Message sent.'];
}

function sisonke_fetch_user_conversations(PDO $pdo, int $userId, string $role): array
{
    sisonke_bootstrap_messaging_schema($pdo);

    $isSeller = sisonke_role_can_act_as($role, 'seller');
    $isBuyer = sisonke_role_can_act_as($role, 'buyer');

    if (!$isSeller && !$isBuyer) {
        return [];
    }

    $filters = [];
    $params = [];

    if ($isBuyer) {
        $filters[] = 'cc.buyer_id = ?';
        $params[] = $userId;
    }
    if ($isSeller) {
        $filters[] = 'cc.seller_id = ?';
        $params[] = $userId;
    }

    $where = implode(' OR ', $filters);

    $stmt = $pdo->prepare(
        "SELECT
            cc.conversation_id,
            cc.campaign_id,
            cc.buyer_id,
            cc.seller_id,
            cc.participant_id,
            cc.updated_at,
            p.name AS product_name,
            s.business_name,
            buyer_user.full_name AS buyer_name,
            (
                SELECT m.body
                FROM campaign_messages m
                WHERE m.conversation_id = cc.conversation_id
                ORDER BY m.created_at DESC, m.message_id DESC
                LIMIT 1
            ) AS last_message,
            (
                SELECT m.created_at
                FROM campaign_messages m
                WHERE m.conversation_id = cc.conversation_id
                ORDER BY m.created_at DESC, m.message_id DESC
                LIMIT 1
            ) AS last_message_at
         FROM campaign_conversations cc
         INNER JOIN group_buy_campaigns c ON c.campaign_id = cc.campaign_id
         INNER JOIN products p ON p.product_id = c.product_id
         INNER JOIN sellers s ON s.seller_id = cc.seller_id
         INNER JOIN users buyer_user ON buyer_user.user_id = cc.buyer_id
         WHERE {$where}
         ORDER BY cc.updated_at DESC, cc.conversation_id DESC"
    );
    $stmt->execute($params);

    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['is_buyer_view'] = $userId === (int) $row['buyer_id'];
        $row['other_party'] = $row['is_buyer_view']
            ? (string) $row['business_name']
            : (string) $row['buyer_name'];
        $row['has_purchased'] = !empty($row['participant_id']);
    }
    unset($row);

    return $rows;
}

function sisonke_conversation_thread_url(int $conversationId): string
{
    return SISONKE_BASE_URL . '/pages/campaign_message.php?id=' . $conversationId;
}

function sisonke_conversation_start_url(int $campaignId): string
{
    return SISONKE_BASE_URL . '/pages/campaign_message.php?campaign=' . $campaignId;
}
