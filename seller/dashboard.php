<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_auth('seller');
require_once dirname(__DIR__) . '/includes/marketplace_service.php';
require_once dirname(__DIR__) . '/includes/i18n.php';

$sellerId = (int) $_SESSION['user_id'];
$summary = sisonke_fetch_seller_summary($pdo, $sellerId);
$campaigns = sisonke_fetch_campaigns($pdo, '', 8, $sellerId);
$hasActiveProduct = (int) ($summary['active_products_count'] ?? 0) > 0;
$hasCampaign = (int) ($summary['campaigns_count'] ?? 0) > 0;
$showSellerSetup = !$hasActiveProduct || !$hasCampaign;
$pageTitle = sisonke_t('seller_dashboard_title');
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section class="st-hero-band">
    <span class="st-kicker"><?= sisonke_e(sisonke_t('seller_portal')) ?></span>
    <h1 class="st-title"><?= sisonke_e(sisonke_t('seller_dashboard_heading')) ?></h1>
    <p class="st-lede"><?= sisonke_e(sisonke_t('seller_dashboard_lede')) ?></p>
</section>

<section class="st-page">
    <?php foreach (sisonke_take_flashes() as $flash): ?>
        <div class="alert alert-<?= sisonke_e($flash['type'] === 'success' ? 'success' : 'danger') ?>"><?= sisonke_e($flash['message']) ?></div>
    <?php endforeach; ?>

    <div class="st-grid st-grid-4 mb-4">
        <div class="st-metric st-metric-red">
            <span class="st-metric-label"><?= sisonke_e(sisonke_t('products')) ?></span>
            <span class="st-metric-value"><?= (int) $summary['products_count'] ?></span>
        </div>
        <div class="st-metric">
            <span class="st-metric-label"><?= sisonke_e(sisonke_t('live_campaigns')) ?></span>
            <span class="st-metric-value"><?= (int) $summary['active_campaigns'] ?></span>
        </div>
        <div class="st-metric st-metric-green">
            <span class="st-metric-label"><?= sisonke_e(sisonke_t('demo_revenue')) ?></span>
            <span class="st-metric-value"><?= sisonke_money($summary['revenue']) ?></span>
        </div>
        <div class="st-metric">
            <span class="st-metric-label"><?= sisonke_e(sisonke_t('open_disputes')) ?></span>
            <span class="st-metric-value"><?= (int) $summary['open_disputes'] ?></span>
        </div>
    </div>

    <?php if ($showSellerSetup): ?>
        <div class="st-card mb-4">
            <div class="st-card-body">
                <h2 class="st-card-title mb-3"><?= sisonke_e(sisonke_t('seller_setup_title')) ?></h2>
                <ol class="st-seller-steps">
                    <li class="st-seller-step<?= $hasActiveProduct ? ' is-complete' : ' is-current' ?>">
                        <span class="st-seller-step-marker" aria-hidden="true"><?= $hasActiveProduct ? '&#10003;' : '1' ?></span>
                        <div class="st-seller-step-body">
                            <strong><?= sisonke_e(sisonke_t('seller_step_add_product')) ?></strong>
                            <p class="st-meta mb-2"><?= sisonke_e(sisonke_t('seller_step_add_product_desc')) ?></p>
                            <?php if (!$hasActiveProduct): ?>
                                <a class="st-btn st-btn-yellow" href="<?= sisonke_e(SISONKE_BASE_URL) ?>/seller/my_products.php"><?= sisonke_e(sisonke_t('add_product')) ?></a>
                            <?php else: ?>
                                <span class="st-badge st-badge-green"><?= sisonke_e(sisonke_t('seller_step_complete')) ?></span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <li class="st-seller-step<?= $hasCampaign ? ' is-complete' : ($hasActiveProduct ? ' is-current' : ' is-locked') ?>">
                        <span class="st-seller-step-marker" aria-hidden="true"><?= $hasCampaign ? '&#10003;' : '2' ?></span>
                        <div class="st-seller-step-body">
                            <strong><?= sisonke_e(sisonke_t('seller_step_launch_campaign')) ?></strong>
                            <p class="st-meta mb-2"><?= sisonke_e(sisonke_t('seller_step_launch_campaign_desc')) ?></p>
                            <?php if ($hasCampaign): ?>
                                <span class="st-badge st-badge-green"><?= sisonke_e(sisonke_t('seller_step_complete')) ?></span>
                            <?php elseif ($hasActiveProduct): ?>
                                <a class="st-btn st-btn-yellow" href="<?= sisonke_e(SISONKE_BASE_URL) ?>/seller/my_products.php?step=campaign"><?= sisonke_e(sisonke_t('create_campaign')) ?></a>
                            <?php else: ?>
                                <span class="st-badge st-badge-muted"><?= sisonke_e(sisonke_t('add_active_product')) ?></span>
                            <?php endif; ?>
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row gap-2 mb-4">
        <a class="st-btn st-btn-yellow" href="<?= sisonke_e(SISONKE_BASE_URL) ?>/seller/my_products.php"><?= sisonke_e(sisonke_t('manage_products')) ?></a>
        <?php if ($hasActiveProduct): ?>
            <a class="st-btn" href="<?= sisonke_e(SISONKE_BASE_URL) ?>/seller/my_products.php?step=campaign"><?= sisonke_e(sisonke_t('create_campaign')) ?></a>
        <?php else: ?>
            <span class="st-btn st-btn-muted" title="<?= sisonke_e(sisonke_t('add_active_product')) ?>"><?= sisonke_e(sisonke_t('create_campaign')) ?></span>
        <?php endif; ?>
    </div>

    <div class="st-card">
        <div class="st-card-body">
            <h2 class="st-card-title mb-3"><?= sisonke_e(sisonke_t('recent_campaigns')) ?></h2>
            <?php if ($campaigns === []): ?>
                <div class="st-empty"><?= sisonke_e(sisonke_t('seller_no_campaigns')) ?></div>
            <?php else: ?>
                <div class="st-table-wrap">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th><?= sisonke_e(sisonke_t('product')) ?></th>
                                <th><?= sisonke_e(sisonke_t('price')) ?></th>
                                <th><?= sisonke_e(sisonke_t('progress')) ?></th>
                                <th><?= sisonke_e(sisonke_t('status')) ?></th>
                                <th><?= sisonke_e(sisonke_t('deadline')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campaigns as $campaign): ?>
                                <?php $progress = sisonke_campaign_progress($campaign); ?>
                                <tr>
                                    <td>
                                        <strong><?= sisonke_e($campaign['product_name']) ?></strong><br>
                                        <span class="st-meta"><?= sisonke_e(sisonke_content_t($campaign['category'])) ?></span>
                                    </td>
                                    <td><?= sisonke_money($campaign['campaign_price']) ?></td>
                                    <td>
                                        <div class="st-progress">
                                            <span style="width: <?= $progress ?>%"></span>
                                        </div>
                                        <small><?= $progress ?>%</small>
                                    </td>
                                    <td><span class="st-badge"><?= sisonke_e(sisonke_content_t($campaign['status'])) ?></span></td>
                                    <td><?= sisonke_e(date('d M Y', strtotime((string) $campaign['deadline']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php
require_once dirname(__DIR__) . '/includes/footer.php';
