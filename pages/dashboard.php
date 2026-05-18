<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_auth('buyer');
require_once dirname(__DIR__) . '/includes/marketplace_service.php';
require_once dirname(__DIR__) . '/includes/i18n.php';

if (sisonke_is_post() && ($_POST['action'] ?? '') === 'confirm_delivery') {
    $result = sisonke_confirm_delivery($pdo, (int) $_SESSION['user_id'], (int) ($_POST['participant_id'] ?? 0));
    sisonke_flash($result['success'] ? 'success' : 'danger', $result['message']);
    sisonke_redirect(SISONKE_BASE_URL . '/pages/dashboard.php');
}

$orders = sisonke_fetch_buyer_orders($pdo, (int) $_SESSION['user_id']);
$pageTitle = sisonke_t('buyer_dashboard_title');
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section class="st-hero-band">
    <span class="st-kicker"><?= sisonke_e(sisonke_t('buyer_portal')) ?></span>
    <h1 class="st-title"><?= sisonke_e(sisonke_t('buyer_orders_heading')) ?></h1>
    <p class="st-lede"><?= sisonke_e(sisonke_t('buyer_orders_lede')) ?></p>
</section>

<section class="st-page">
    <?php foreach (sisonke_take_flashes() as $flash): ?>
        <div class="alert alert-<?= sisonke_e($flash['type'] === 'success' ? 'success' : 'danger') ?>"><?= sisonke_e($flash['message']) ?></div>
    <?php endforeach; ?>

    <div class="st-grid st-grid-3 mb-4">
        <div class="st-metric st-metric-red">
            <span class="st-metric-label"><?= sisonke_e(sisonke_t('orders_joined')) ?></span>
            <span class="st-metric-value"><?= count($orders) ?></span>
        </div>
        <div class="st-metric">
            <span class="st-metric-label"><?= sisonke_e(sisonke_t('pending_confirmations')) ?></span>
            <span class="st-metric-value"><?= count(array_filter($orders, static fn (array $order): bool => !(bool) $order['has_confirmed_delivery'])) ?></span>
        </div>
        <div class="st-metric st-metric-green">
            <span class="st-metric-label"><?= sisonke_e(sisonke_t('escrow_protection')) ?></span>
            <span class="st-metric-value"><?= sisonke_e(sisonke_t('on')) ?></span>
        </div>
    </div>

    <?php if ($orders === []): ?>
        <div class="st-empty">
            <?= sisonke_e(sisonke_t('no_orders')) ?>
            <a href="<?= sisonke_e(SISONKE_BASE_URL) ?>/pages/campaigns.php"><?= sisonke_e(sisonke_t('browse_marketplace_campaigns')) ?></a>.
        </div>
    <?php else: ?>
        <div class="st-table-wrap">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th><?= sisonke_e(sisonke_t('table_campaign')) ?></th>
                        <th><?= sisonke_e(sisonke_t('table_seller')) ?></th>
                        <th><?= sisonke_e(sisonke_t('table_quantity')) ?></th>
                        <th><?= sisonke_e(sisonke_t('table_paid')) ?></th>
                        <th><?= sisonke_e(sisonke_t('table_escrow')) ?></th>
                        <th><?= sisonke_e(sisonke_t('table_reference')) ?></th>
                        <th><?= sisonke_e(sisonke_t('table_action')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong><?= sisonke_e($order['product_name']) ?></strong><br>
                                <span class="st-meta"><?= sisonke_e(sisonke_t('status_campaign', ['status' => sisonke_content_t($order['campaign_status'])])) ?></span>
                            </td>
                            <td><?= sisonke_e($order['business_name']) ?></td>
                            <td><?= (int) $order['quantity'] ?></td>
                            <td><?= sisonke_money($order['amount_paid']) ?></td>
                            <td><span class="st-badge <?= $order['escrow_status'] === 'released' ? 'st-badge-green' : 'st-badge' ?>"><?= sisonke_e(sisonke_content_t($order['escrow_status'] ?? 'held')) ?></span></td>
                            <td><?= sisonke_e($order['reference_number'] ?? sisonke_t('pending')) ?></td>
                            <td>
                                <?php if ((bool) $order['has_confirmed_delivery']): ?>
                                    <span class="st-badge st-badge-green"><?= sisonke_e(sisonke_t('confirmed')) ?></span>
                                <?php else: ?>
                                    <form method="post" action="<?= sisonke_e(SISONKE_BASE_URL) ?>/pages/dashboard.php">
                                        <input type="hidden" name="action" value="confirm_delivery">
                                        <input type="hidden" name="participant_id" value="<?= (int) $order['participant_id'] ?>">
                                        <button class="st-btn st-btn-yellow" type="submit"><?= sisonke_e(sisonke_t('confirm_delivery')) ?></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php
require_once dirname(__DIR__) . '/includes/footer.php';
