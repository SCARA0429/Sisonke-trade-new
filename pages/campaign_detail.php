<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/marketplace_service.php';
require_once dirname(__DIR__) . '/includes/payfast_service.php';
require_once dirname(__DIR__) . '/includes/i18n.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$campaignId = max(0, (int) ($_GET['id'] ?? $_POST['campaign_id'] ?? 0));
$campaign = $campaignId > 0 ? sisonke_fetch_campaign($pdo, $campaignId) : null;

if (!$campaign) {
    $pageTitle = sisonke_t('campaign_not_found_title');
    require_once dirname(__DIR__) . '/includes/header.php';
    echo '<section class="st-page"><div class="st-empty">' . sisonke_e(sisonke_t('campaign_not_found')) . ' <a href="' . sisonke_e(SISONKE_BASE_URL) . '/pages/campaigns.php">' . sisonke_e(sisonke_t('campaign_back_marketplace')) . '</a></div></section>';
    require_once dirname(__DIR__) . '/includes/footer.php';
    exit;
}

$pageTitle = $campaign['product_name'];
$progress = sisonke_campaign_progress($campaign);
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section class="st-hero-band">
    <span class="st-kicker"><?= sisonke_e(sisonke_content_t($campaign['category'])) ?></span>
    <h1 class="st-title"><?= sisonke_e($campaign['product_name']) ?></h1>
    <p class="st-lede"><?= sisonke_e(sisonke_t('campaign_detail_lede', ['seller' => $campaign['business_name']])) ?></p>
</section>

<section class="st-page">
    <?php foreach (sisonke_take_flashes() as $flash): ?>
        <div class="alert alert-<?= sisonke_e($flash['type'] === 'success' ? 'success' : 'danger') ?>"><?= sisonke_e($flash['message']) ?></div>
    <?php endforeach; ?>

    <div class="st-grid st-grid-2">
        <article class="st-card">
            <div class="st-product-media">
                <img src="<?= sisonke_e(sisonke_campaign_image_url($campaign)) ?>" alt="<?= sisonke_e($campaign['product_name']) ?>" loading="lazy">
            </div>
            <div class="st-card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="st-badge <?= $campaign['status'] === 'active' ? 'st-badge-green' : 'st-badge-muted' ?>"><?= sisonke_e(sisonke_content_t($campaign['status'])) ?></span>
                    <span class="st-badge"><?= sisonke_e(sisonke_t('campaign_committed', ['progress' => $progress])) ?></span>
                    <span class="st-badge"><?= sisonke_e(sisonke_t('campaign_seller_badge', ['status' => sisonke_content_t($campaign['verification_status'])])) ?></span>
                </div>
                <p class="fs-5"><?= sisonke_e(sisonke_content_t($campaign['description'])) ?></p>
                <div class="st-progress mb-3">
                    <span style="width: <?= $progress ?>%"></span>
                </div>
                <dl class="row mb-0">
                    <dt class="col-sm-5"><?= sisonke_e(sisonke_t('campaign_price')) ?></dt>
                    <dd class="col-sm-7"><?= sisonke_money($campaign['campaign_price']) ?></dd>
                    <dt class="col-sm-5"><?= sisonke_e(sisonke_t('normal_unit_price')) ?></dt>
                    <dd class="col-sm-7"><?= sisonke_money($campaign['unit_price']) ?></dd>
                    <dt class="col-sm-5"><?= sisonke_e(sisonke_t('target_quantity')) ?></dt>
                    <dd class="col-sm-7"><?= (int) $campaign['current_quantity'] ?> / <?= (int) $campaign['target_quantity'] ?></dd>
                    <dt class="col-sm-5"><?= sisonke_e(sisonke_t('deadline')) ?></dt>
                    <dd class="col-sm-7"><?= sisonke_e(date('d M Y H:i', strtotime((string) $campaign['deadline']))) ?></dd>
                </dl>
            </div>
        </article>

        <aside class="st-card">
            <div class="st-card-body">
                <h2 class="st-card-title mb-3"><?= sisonke_e(sisonke_t('join_campaign')) ?></h2>
                <?php if ($campaign['status'] !== 'active'): ?>
                    <div class="alert alert-warning"><?= sisonke_e(sisonke_t('campaign_currently_status', ['status' => sisonke_content_t($campaign['status'])])) ?></div>
                <?php elseif (sisonke_current_user_id() !== null && sisonke_role_can_act_as(sisonke_current_role(), 'buyer')): ?>
                    <form method="post" action="<?= sisonke_e(SISONKE_BASE_URL) ?>/pages/payfast_checkout.php">
                        <input type="hidden" name="campaign_id" value="<?= (int) $campaign['campaign_id'] ?>">
                        <div class="mb-3">
                            <label class="st-label" for="quantity"><?= sisonke_e(sisonke_t('quantity')) ?></label>
                            <input class="st-form-control" id="quantity" type="number" name="quantity" value="1" min="1" max="50" required>
                        </div>
                        <div class="mb-3">
                            <span class="st-badge st-badge-green"><?= sisonke_e(sisonke_t(sisonke_payfast_gateway_label_key())) ?></span>
                            <p class="st-meta mt-2 mb-0"><?= sisonke_e(sisonke_t(sisonke_payfast_is_sandbox() ? 'payfast_review_notice' : 'payfast_review_notice_live')) ?></p>
                        </div>
                        <button class="st-btn st-btn-yellow" type="submit"><?= sisonke_e(sisonke_t(sisonke_payfast_continue_label_key())) ?></button>
                    </form>
                <?php else: ?>
                    <p class="st-meta"><?= sisonke_e(sisonke_t('buyer_join_notice')) ?></p>
                    <a class="st-btn st-btn-yellow" href="<?= sisonke_e(SISONKE_BASE_URL) ?>/pages/login.php?return=<?= urlencode(SISONKE_BASE_URL . '/pages/campaign_detail.php?id=' . (int) $campaign['campaign_id']) ?>"><?= sisonke_e(sisonke_t('login_to_join')) ?></a>
                    <a class="st-btn st-btn-outline mt-2" href="<?= sisonke_e(SISONKE_BASE_URL) ?>/pages/register.php"><?= sisonke_e(sisonke_t('register_as_buyer')) ?></a>
                <?php endif; ?>

                <hr>
                <h3 class="h5 fw-bold"><?= sisonke_e(sisonke_t('seller_trust')) ?></h3>
                <p class="mb-1"><strong><?= sisonke_e($campaign['business_name']) ?></strong></p>
                <p class="st-meta mb-0"><?= sisonke_e(sisonke_t('reputation_notice', ['score' => $campaign['reputation_score']])) ?></p>
            </div>
        </aside>
    </div>
</section>
<?php
require_once dirname(__DIR__) . '/includes/footer.php';
