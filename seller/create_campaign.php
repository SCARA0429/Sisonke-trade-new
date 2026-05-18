<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_auth('seller');
require_once dirname(__DIR__) . '/includes/marketplace_service.php';
require_once dirname(__DIR__) . '/includes/i18n.php';

$sellerId = (int) $_SESSION['user_id'];

if (sisonke_is_post()) {
    $result = sisonke_create_campaign($pdo, $sellerId, $_POST, $_FILES);
    sisonke_flash($result['success'] ? 'success' : 'danger', $result['message']);
    sisonke_redirect($result['success']
        ? SISONKE_BASE_URL . '/seller/dashboard.php'
        : SISONKE_BASE_URL . '/seller/create_campaign.php');
}

$products = array_filter(
    sisonke_fetch_seller_products($pdo, $sellerId),
    static fn (array $product): bool => (bool) $product['is_active']
);
$pageTitle = sisonke_t('create_campaign_title');
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section class="st-hero-band">
    <span class="st-kicker"><?= sisonke_e(sisonke_t('group_buy_setup')) ?></span>
    <h1 class="st-title"><?= sisonke_e(sisonke_t('create_campaign_heading')) ?></h1>
    <p class="st-lede"><?= sisonke_e(sisonke_t('create_campaign_lede')) ?></p>
</section>

<section class="st-page">
    <?php foreach (sisonke_take_flashes() as $flash): ?>
        <div class="alert alert-<?= sisonke_e($flash['type'] === 'success' ? 'success' : 'danger') ?>"><?= sisonke_e($flash['message']) ?></div>
    <?php endforeach; ?>

    <div class="st-grid st-grid-2">
        <article class="st-card">
            <div class="st-card-body">
                <h2 class="st-card-title mb-3"><?= sisonke_e(sisonke_t('campaign_details')) ?></h2>
                <?php if ($products === []): ?>
                    <div class="st-empty">
                        <?= sisonke_e(sisonke_t('add_active_product')) ?>
                        <a href="<?= sisonke_e(SISONKE_BASE_URL) ?>/seller/my_products.php"><?= sisonke_e(sisonke_t('go_to_products')) ?></a>.
                    </div>
                <?php else: ?>
                    <form method="post" action="<?= sisonke_e(SISONKE_BASE_URL) ?>/seller/create_campaign.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="st-label" for="product_id"><?= sisonke_e(sisonke_t('product')) ?></label>
                            <select class="st-select" id="product_id" name="product_id" required>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?= (int) $product['product_id'] ?>">
                                        <?= sisonke_e($product['name']) ?> - <?= sisonke_money($product['unit_price']) ?> - <?= (int) $product['quantity_available'] ?> <?= sisonke_e(sisonke_t('in_stock')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="st-label" for="campaign_price"><?= sisonke_e(sisonke_t('campaign_price')) ?></label>
                                <input class="st-form-control" id="campaign_price" type="number" name="campaign_price" step="0.01" min="1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="st-label" for="deadline"><?= sisonke_e(sisonke_t('deadline')) ?></label>
                                <input class="st-form-control" id="deadline" type="datetime-local" name="deadline" required>
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label class="st-label" for="min_participants"><?= sisonke_e(sisonke_t('minimum_buyers')) ?></label>
                                <input class="st-form-control" id="min_participants" type="number" name="min_participants" min="1" value="5" required>
                            </div>
                            <div class="col-md-4">
                                <label class="st-label" for="max_participants"><?= sisonke_e(sisonke_t('maximum_units')) ?></label>
                                <input class="st-form-control" id="max_participants" type="number" name="max_participants" min="1" value="50" required>
                            </div>
                            <div class="col-md-4">
                                <label class="st-label" for="target_quantity"><?= sisonke_e(sisonke_t('target_units')) ?></label>
                                <input class="st-form-control" id="target_quantity" type="number" name="target_quantity" min="1" value="25" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="st-label" for="campaign_image"><?= sisonke_e(sisonke_t('campaign_image')) ?></label>
                            <input class="st-form-control" id="campaign_image" type="file" name="campaign_image" accept="image/jpeg,image/png,image/webp,image/gif">
                            <small class="st-meta"><?= sisonke_e(sisonke_t('campaign_image_upload_help')) ?></small>
                        </div>
                        <div class="mt-3">
                            <label class="st-label" for="campaign_image_url"><?= sisonke_e(sisonke_t('campaign_image_url')) ?></label>
                            <input class="st-form-control" id="campaign_image_url" type="url" name="campaign_image_url" maxlength="255" placeholder="<?= sisonke_e(sisonke_t('campaign_image_url_placeholder')) ?>">
                            <small class="st-meta"><?= sisonke_e(sisonke_t('campaign_image_url_help')) ?></small>
                        </div>
                        <button class="st-btn st-btn-yellow mt-3" type="submit"><?= sisonke_e(sisonke_t('launch_campaign')) ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </article>

        <aside class="st-card">
            <div class="st-card-body">
                <h2 class="st-card-title mb-3"><?= sisonke_e(sisonke_t('campaign_rules')) ?></h2>
                <p><?= sisonke_e(sisonke_t('campaign_rules_text')) ?></p>
                <p class="st-meta mb-0"><?= sisonke_e(sisonke_t('demo_money_notice')) ?></p>
            </div>
        </aside>
    </div>
</section>
<?php
require_once dirname(__DIR__) . '/includes/footer.php';
