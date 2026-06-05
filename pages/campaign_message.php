<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/messaging_service.php';
require_once dirname(__DIR__) . '/includes/i18n.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$role = (string) ($_SESSION['user_role'] ?? '');

if (!sisonke_role_can_act_as($role, 'buyer') && !sisonke_role_can_act_as($role, 'seller')) {
    sisonke_redirect(SISONKE_BASE_URL . '/pages/login.php');
}

if (sisonke_is_post() && ($_POST['action'] ?? '') === 'send_message') {
    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    $result = sisonke_send_campaign_message($pdo, $conversationId, $userId, (string) ($_POST['body'] ?? ''));
    sisonke_flash($result['success'] ? 'success' : 'danger', $result['message']);
    sisonke_redirect(sisonke_conversation_thread_url($conversationId));
}

$conversationId = (int) ($_GET['id'] ?? 0);
$campaignId = (int) ($_GET['campaign'] ?? 0);

if ($conversationId <= 0 && $campaignId > 0) {
    if (!sisonke_role_can_act_as($role, 'buyer')) {
        sisonke_flash('danger', sisonke_t('messages_buyer_only_start'));
        sisonke_redirect(SISONKE_BASE_URL . '/pages/campaign_detail.php?id=' . $campaignId);
    }

    $started = sisonke_get_or_create_conversation($pdo, $campaignId, $userId);
    if (empty($started['success'])) {
        sisonke_flash('danger', (string) ($started['message'] ?? sisonke_t('messages_start_failed')));
        sisonke_redirect(SISONKE_BASE_URL . '/pages/campaign_detail.php?id=' . $campaignId);
    }

    sisonke_redirect(sisonke_conversation_thread_url((int) $started['conversation_id']));
}

$conversation = $conversationId > 0 ? sisonke_fetch_conversation($pdo, $conversationId) : null;

if (!$conversation || !sisonke_user_can_access_conversation($conversation, $userId)) {
    $pageTitle = sisonke_t('messages_not_found_title');
    require_once dirname(__DIR__) . '/includes/header.php';
    echo '<section class="st-page"><div class="st-empty">' . sisonke_e(sisonke_t('messages_not_found'))
        . ' <a href="' . sisonke_e(sisonke_messages_inbox_url()) . '">' . sisonke_e(sisonke_t('messages_back_inbox')) . '</a></div></section>';
    require_once dirname(__DIR__) . '/includes/footer.php';
    exit;
}

$messages = sisonke_fetch_conversation_messages($pdo, $conversationId);
$isBuyer = $userId === (int) $conversation['buyer_id'];
$otherParty = $isBuyer ? (string) $conversation['business_name'] : (string) $conversation['buyer_name'];
$inboxHref = sisonke_messages_inbox_url();
$hasPurchased = !empty($conversation['participant_id']);

$pageTitle = sisonke_t('messages_thread_title', ['campaign' => (string) $conversation['product_name']]);
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section class="st-hero-band">
    <span class="st-kicker"><?= sisonke_e(sisonke_t('messages_kicker')) ?></span>
    <h1 class="st-title"><?= sisonke_e((string) $conversation['product_name']) ?></h1>
    <p class="st-lede">
        <?= sisonke_e(sisonke_t('messages_thread_lede', ['name' => $otherParty])) ?>
        <?php if ($hasPurchased): ?>
            <span class="st-badge st-badge-green ms-1"><?= sisonke_e(sisonke_t('messages_purchased_badge')) ?></span>
        <?php endif; ?>
    </p>
</section>

<section class="st-page">
    <?php foreach (sisonke_take_flashes() as $flash): ?>
        <div class="alert alert-<?= sisonke_e($flash['type'] === 'success' ? 'success' : 'danger') ?>"><?= sisonke_e($flash['message']) ?></div>
    <?php endforeach; ?>

    <p class="mb-3">
        <a class="st-link" href="<?= sisonke_e($inboxHref) ?>"><?= sisonke_e(sisonke_t('messages_back_inbox')) ?></a>
        &middot;
        <a class="st-link" href="<?= sisonke_e(SISONKE_BASE_URL) ?>/pages/campaign_detail.php?id=<?= (int) $conversation['campaign_id'] ?>"><?= sisonke_e(sisonke_t('messages_view_campaign')) ?></a>
    </p>

    <div class="st-message-thread" aria-live="polite">
        <?php if ($messages === []): ?>
            <div class="st-empty st-message-empty"><?= sisonke_e(sisonke_t('messages_empty_thread')) ?></div>
        <?php else: ?>
            <?php foreach ($messages as $message): ?>
                <?php $isMine = (int) $message['sender_id'] === $userId; ?>
                <article class="st-message-bubble<?= $isMine ? ' is-mine' : ' is-theirs' ?>">
                    <header class="st-message-meta">
                        <strong><?= sisonke_e($isMine ? sisonke_t('messages_you') : (string) $message['sender_name']) ?></strong>
                        <time datetime="<?= sisonke_e(date('c', strtotime((string) $message['created_at']))) ?>"><?= sisonke_e(date('d M Y H:i', strtotime((string) $message['created_at']))) ?></time>
                    </header>
                    <p><?= nl2br(sisonke_e((string) $message['body'])) ?></p>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <form class="st-message-compose mt-4" method="post" action="<?= sisonke_e(sisonke_conversation_thread_url($conversationId)) ?>">
        <input type="hidden" name="action" value="send_message">
        <input type="hidden" name="conversation_id" value="<?= $conversationId ?>">
        <label class="st-label" for="message-body"><?= sisonke_e(sisonke_t('messages_compose_label')) ?></label>
        <textarea class="st-form-control" id="message-body" name="body" rows="4" maxlength="2000" required placeholder="<?= sisonke_e(sisonke_t('messages_compose_placeholder')) ?>"></textarea>
        <button class="st-btn st-btn-yellow mt-3" type="submit"><?= sisonke_e(sisonke_t('messages_send_button')) ?></button>
    </form>
</section>
<?php
require_once dirname(__DIR__) . '/includes/footer.php';
