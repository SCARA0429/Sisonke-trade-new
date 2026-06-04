<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_auth('seller');
require_once dirname(__DIR__) . '/includes/messaging_service.php';
require_once dirname(__DIR__) . '/includes/i18n.php';

$sellerId = (int) $_SESSION['user_id'];
$conversations = sisonke_fetch_user_conversations($pdo, $sellerId, (string) $_SESSION['user_role']);
$conversations = array_values(array_filter(
    $conversations,
    static fn (array $row): bool => empty($row['is_buyer_view'])
));

$pageTitle = sisonke_t('messages_inbox_title');
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section class="st-hero-band">
    <span class="st-kicker"><?= sisonke_e(sisonke_t('messages_kicker')) ?></span>
    <h1 class="st-title"><?= sisonke_e(sisonke_t('messages_inbox_heading_seller')) ?></h1>
    <p class="st-lede"><?= sisonke_e(sisonke_t('messages_inbox_lede_seller')) ?></p>
</section>

<section class="st-page">
    <?php if ($conversations === []): ?>
        <div class="st-empty"><?= sisonke_e(sisonke_t('messages_inbox_empty_seller')) ?></div>
    <?php else: ?>
        <div class="st-message-inbox">
            <?php foreach ($conversations as $thread): ?>
                <a class="st-message-inbox-item" href="<?= sisonke_e(sisonke_conversation_thread_url((int) $thread['conversation_id'])) ?>">
                    <div class="st-message-inbox-main">
                        <strong><?= sisonke_e((string) $thread['product_name']) ?></strong>
                        <span class="st-meta"><?= sisonke_e(sisonke_t('messages_with_buyer', ['name' => (string) $thread['other_party']])) ?></span>
                        <?php if (!empty($thread['last_message'])): ?>
                            <p class="st-message-preview mb-0"><?= sisonke_e((string) $thread['last_message']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="st-message-inbox-meta">
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
