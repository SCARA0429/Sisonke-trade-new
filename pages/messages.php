<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/messaging_service.php';
require_once dirname(__DIR__) . '/includes/i18n.php';

$role = (string) ($_SESSION['user_role'] ?? '');
if (!sisonke_role_can_act_as($role, 'buyer') && !sisonke_role_can_act_as($role, 'seller')) {
    sisonke_redirect(SISONKE_BASE_URL . '/pages/login.php');
}

$userId = (int) $_SESSION['user_id'];
$canBuy = sisonke_role_can_act_as($role, 'buyer');
$canSell = sisonke_role_can_act_as($role, 'seller');
$conversations = sisonke_fetch_user_conversations($pdo, $userId, $role);

if ($canBuy && $canSell) {
    $inboxHeading = sisonke_t('messages_inbox_heading_unified');
    $inboxLede = sisonke_t('messages_inbox_lede_unified');
    $inboxEmpty = sisonke_t('messages_inbox_empty_unified');
} elseif ($canSell) {
    $inboxHeading = sisonke_t('messages_inbox_heading_seller');
    $inboxLede = sisonke_t('messages_inbox_lede_seller');
    $inboxEmpty = sisonke_t('messages_inbox_empty_seller');
} else {
    $inboxHeading = sisonke_t('messages_inbox_heading');
    $inboxLede = sisonke_t('messages_inbox_lede_buyer');
    $inboxEmpty = sisonke_t('messages_inbox_empty_buyer');
}

$pageTitle = sisonke_t('messages_inbox_title');
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section class="st-hero-band">
    <span class="st-kicker"><?= sisonke_e(sisonke_t('messages_kicker')) ?></span>
    <h1 class="st-title"><?= sisonke_e($inboxHeading) ?></h1>
    <p class="st-lede"><?= sisonke_e($inboxLede) ?></p>
</section>

<section class="st-page">
    <?php if ($conversations === []): ?>
        <div class="st-empty">
            <?= sisonke_e($inboxEmpty) ?>
            <?php if ($canBuy): ?>
                <a href="<?= sisonke_e(SISONKE_BASE_URL) ?>/pages/campaigns.php"><?= sisonke_e(sisonke_t('browse_marketplace_campaigns')) ?></a>.
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="st-message-inbox">
            <?php foreach ($conversations as $thread): ?>
                <?php
                $isBuyerView = !empty($thread['is_buyer_view']);
                $partyLabel = $isBuyerView
                    ? sisonke_t('messages_with_seller', ['name' => (string) $thread['other_party']])
                    : sisonke_t('messages_with_buyer', ['name' => (string) $thread['other_party']]);
                ?>
                <a class="st-message-inbox-item" href="<?= sisonke_e(sisonke_conversation_thread_url((int) $thread['conversation_id'])) ?>">
                    <div class="st-message-inbox-main">
                        <strong><?= sisonke_e((string) $thread['product_name']) ?></strong>
                        <span class="st-meta"><?= sisonke_e($partyLabel) ?></span>
                        <?php if (!empty($thread['last_message'])): ?>
                            <p class="st-message-preview mb-0"><?= sisonke_e((string) $thread['last_message']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="st-message-inbox-meta">
                        <?php if ($canBuy && $canSell): ?>
                            <span class="st-badge"><?= sisonke_e($isBuyerView ? sisonke_t('messages_role_buying') : sisonke_t('messages_role_selling')) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($thread['has_purchased'])): ?>
                            <span class="st-badge st-badge-green"><?= sisonke_e(sisonke_t('messages_purchased_badge')) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($thread['last_message_at'])): ?>
                            <time class="st-meta"><?= sisonke_e(date('d M H:i', strtotime((string) $thread['last_message_at']))) ?></time>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php
require_once dirname(__DIR__) . '/includes/footer.php';
