<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_auth('buyer');
require_once dirname(__DIR__) . '/includes/payfast_service.php';
require_once dirname(__DIR__) . '/includes/i18n.php';

if (!sisonke_is_post()) {
    sisonke_redirect(SISONKE_BASE_URL . '/pages/campaigns.php');
}

$campaignId = (int) ($_POST['campaign_id'] ?? 0);
$quantity = (int) ($_POST['quantity'] ?? 1);
$campaign = $campaignId > 0 ? sisonke_fetch_campaign($pdo, $campaignId) : null;

if (!$campaign) {
    sisonke_flash('danger', sisonke_t('payfast_campaign_missing'));
    sisonke_redirect(SISONKE_BASE_URL . '/pages/campaigns.php');
}

if ($campaign['status'] !== 'active') {
    sisonke_flash('danger', sisonke_t('payfast_campaign_closed'));
    sisonke_redirect(SISONKE_BASE_URL . '/pages/campaign_detail.php?id=' . $campaignId);
}

$checkoutReady = sisonke_payfast_checkout_ready();
if (!$checkoutReady['ready']) {
    sisonke_flash('danger', $checkoutReady['message']);
    sisonke_redirect(SISONKE_BASE_URL . '/pages/campaign_detail.php?id=' . $campaignId);
}

$buyerId = (int) $_SESSION['user_id'];
$stmtBuyer = $pdo->prepare('INSERT IGNORE INTO buyers (buyer_id, delivery_address) VALUES (?, ?)');
$stmtBuyer->execute([$buyerId, '']);

$checkout = sisonke_payfast_create_intent(
    $pdo,
    $campaign,
    $buyerId,
    (string) $_SESSION['user_email'],
    (string) $_SESSION['user_name'],
    $quantity
);
$intent = $checkout['intent'];
$payfastData = $checkout['data'];

$pageTitle = sisonke_t('payfast_title');
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section class="st-hero-band">
    <span class="st-kicker"><?= sisonke_e(sisonke_t(sisonke_payfast_gateway_label_key())) ?></span>
    <h1 class="st-title"><?= sisonke_e(sisonke_t('payfast_heading')) ?></h1>
    <p class="st-lede"><?= sisonke_e(sisonke_t(sisonke_payfast_is_sandbox() ? 'payfast_lede' : 'payfast_lede_live')) ?></p>
</section>

<section class="st-page">
    <?php if (sisonke_payfast_uses_local_urls()): ?>
        <div class="alert alert-warning">
            <?= sisonke_e(sisonke_t('payfast_local_warning')) ?>
        </div>
    <?php endif; ?>

    <div class="st-grid st-grid-2">
        <article class="st-card">
            <div class="st-card-body">
                <h2 class="st-card-title mb-3"><?= sisonke_e(sisonke_t('payment_summary')) ?></h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5"><?= sisonke_e(sisonke_t('reference')) ?></dt>
                    <dd class="col-sm-7"><?= sisonke_e($intent['reference']) ?></dd>
                    <dt class="col-sm-5"><?= sisonke_e(sisonke_t('table_campaign')) ?></dt>
                    <dd class="col-sm-7"><?= sisonke_e($intent['item_name']) ?></dd>
                    <dt class="col-sm-5"><?= sisonke_e(sisonke_t('table_seller')) ?></dt>
                    <dd class="col-sm-7"><?= sisonke_e($campaign['business_name']) ?></dd>
                    <dt class="col-sm-5"><?= sisonke_e(sisonke_t('quantity')) ?></dt>
                    <dd class="col-sm-7"><?= (int) $intent['quantity'] ?></dd>
                    <dt class="col-sm-5"><?= sisonke_e(sisonke_t('amount')) ?></dt>
                    <dd class="col-sm-7"><?= sisonke_money($intent['amount']) ?></dd>
                    <dt class="col-sm-5"><?= sisonke_e(sisonke_t('gateway')) ?></dt>
                    <dd class="col-sm-7"><?= sisonke_e(sisonke_t(sisonke_payfast_gateway_label_key())) ?></dd>
                </dl>
            </div>
        </article>

        <article class="st-card">
            <div class="st-card-body">
                <h2 class="st-card-title mb-3"><?= sisonke_e(sisonke_t(sisonke_payfast_is_sandbox() ? 'sandbox_actions' : 'payfast_actions')) ?></h2>
                <p><?= sisonke_e(sisonke_t(sisonke_payfast_is_sandbox() ? 'sandbox_actions_text' : 'payfast_actions_text')) ?></p>

                <form method="post" action="<?= sisonke_e(sisonke_payfast_endpoint()) ?>" class="mb-3">
                    <?php foreach ($payfastData as $name => $value): ?>
                        <input type="hidden" name="<?= sisonke_e($name) ?>" value="<?= sisonke_e($value) ?>">
                    <?php endforeach; ?>
                    <button class="st-btn st-btn-yellow" type="submit"><?= sisonke_e(sisonke_t(sisonke_payfast_continue_label_key())) ?></button>
                </form>

                <?php if (sisonke_payfast_simulation_allowed()): ?>
                    <form method="post" action="<?= sisonke_e(SISONKE_BASE_URL) ?>/pages/payfast_return.php">
                        <input type="hidden" name="ref" value="<?= sisonke_e($intent['reference']) ?>">
                        <input type="hidden" name="payment_status" value="COMPLETE">
                        <button class="st-btn" type="submit"><?= sisonke_e(sisonke_t('simulate_payfast_success')) ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </article>
    </div>

    <?php if (sisonke_payfast_is_sandbox()): ?>
    <article class="st-card mt-4">
        <div class="st-card-body">
            <h2 class="st-card-title mb-3"><?= sisonke_e(sisonke_t('payfast_fields_sent')) ?></h2>
            <div class="st-table-wrap">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th><?= sisonke_e(sisonke_t('field')) ?></th>
                            <th><?= sisonke_e(sisonke_t('value')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payfastData as $name => $value): ?>
                            <tr>
                                <td><?= sisonke_e($name) ?></td>
                                <td><?= sisonke_e($value) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </article>
    <?php endif; ?>
</section>
<?php
require_once dirname(__DIR__) . '/includes/footer.php';
