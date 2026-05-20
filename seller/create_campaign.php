<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_auth('seller');
require_once dirname(__DIR__) . '/includes/marketplace_service.php';
require_once dirname(__DIR__) . '/includes/i18n.php';

$sellerId = (int) $_SESSION['user_id'];
$productsUrl = SISONKE_BASE_URL . '/seller/my_products.php';

if (!sisonke_is_post()) {
    $redirectUrl = $productsUrl . '?step=campaign';
    $productId = (int) ($_GET['product_id'] ?? 0);
    if ($productId > 0) {
        $redirectUrl .= '&launch=' . $productId;
    }
    sisonke_redirect($redirectUrl);
}

if (sisonke_is_post()) {
    $result = sisonke_create_campaign($pdo, $sellerId, $_POST, $_FILES);
    $productId = (int) ($_POST['product_id'] ?? 0);
    sisonke_flash($result['success'] ? 'success' : 'danger', $result['message']);
    sisonke_redirect($result['success']
        ? SISONKE_BASE_URL . '/seller/dashboard.php'
        : $productsUrl . '?launch=' . $productId);
}

$campaignFormState = sisonke_campaign_form_state($pdo, $sellerId, (int) ($_GET['product_id'] ?? 0));
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

    <p class="mb-3">
        <a class="st-link" href="<?= sisonke_e($productsUrl) ?>"><?= sisonke_e(sisonke_t('back_to_products')) ?></a>
    </p>

    <div class="st-grid st-grid-2">
        <article class="st-card">
            <div class="st-card-body">
                <h2 class="st-card-title mb-3"><?= sisonke_e(sisonke_t('campaign_details')) ?></h2>
                <p class="st-meta mb-3"><?= sisonke_e(sisonke_t('campaign_catalogue_hint')) ?></p>
                <?php
                $campaignFormAction = SISONKE_BASE_URL . '/seller/create_campaign.php';
                $lockProductSelect = false;
                $formIdPrefix = '';
                require __DIR__ . '/partials/campaign_form.php';
                ?>
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
