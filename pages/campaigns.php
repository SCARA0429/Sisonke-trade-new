<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/marketplace_service.php';
require_once dirname(__DIR__) . '/includes/i18n.php';

$search = trim((string) ($_GET['q'] ?? ''));
$campaigns = sisonke_fetch_campaigns($pdo, $search);
$pageTitle = sisonke_t('marketplace_title');
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section class="st-hero-band">
    <span class="st-kicker"><?= sisonke_e(sisonke_t('marketplace_kicker')) ?></span>
    <h1 class="st-title"><?= sisonke_e(sisonke_t('marketplace_heading')) ?></h1>
    <p class="st-lede"><?= sisonke_e(sisonke_t('marketplace_lede')) ?></p>
</section>

<section class="st-page">
    <?php foreach (sisonke_take_flashes() as $flash): ?>
        <div class="alert alert-<?= sisonke_e($flash['type'] === 'success' ? 'success' : 'danger') ?>"><?= sisonke_e($flash['message']) ?></div>
    <?php endforeach; ?>

    <form class="row g-2 align-items-end mb-4" method="get" action="<?= sisonke_e(SISONKE_BASE_URL) ?>/pages/campaigns.php">
        <div class="col-12 col-md-9 col-lg-10">
            <label class="st-label" for="q"><?= sisonke_e(sisonke_t('marketplace_search_label')) ?></label>
            <input class="st-form-control" id="q" type="search" name="q" value="<?= sisonke_e($search) ?>" placeholder="<?= sisonke_e(sisonke_t('marketplace_search_placeholder')) ?>">
        </div>
        <div class="col-12 col-md-3 col-lg-2">
            <button class="st-btn st-btn-yellow w-100" type="submit"><?= sisonke_e(sisonke_t('marketplace_search_button')) ?></button>
        </div>
    </form>

    <?php if ($campaigns === []): ?>
        <div class="st-empty"><?= sisonke_e(sisonke_t('marketplace_no_campaigns')) ?></div>
    <?php else: ?>
        <div class="st-grid st-grid-3">
            <?php foreach ($campaigns as $campaign): ?>
                <?php $progress = sisonke_campaign_progress($campaign); ?>
                <article class="st-card">
                    <div class="st-product-media">
                        <img src="<?= sisonke_e(sisonke_campaign_image_url($campaign)) ?>" alt="<?= sisonke_e($campaign['product_name']) ?>" loading="lazy">
                    </div>
                    <div class="st-card-body">
                        <div class="d-flex justify-content-between gap-2 align-items-start mb-2">
                            <span class="st-badge <?= $campaign['status'] === 'active' ? 'st-badge-green' : 'st-badge-muted' ?>"><?= sisonke_e(sisonke_content_t($campaign['status'])) ?></span>
                            <span class="st-badge"><?= $progress ?>%</span>
                        </div>
                        <h2 class="st-card-title"><?= sisonke_e($campaign['product_name']) ?></h2>
                        <p class="st-meta mb-2"><?= sisonke_e($campaign['business_name']) ?> - <?= sisonke_e(sisonke_content_t($campaign['category'])) ?></p>
                        <p><?= sisonke_e(sisonke_content_t($campaign['description'])) ?></p>
                        <div class="st-progress mb-3" aria-label="<?= sisonke_e(sisonke_t('campaign_progress_label')) ?>">
                            <span style="width: <?= $progress ?>%"></span>
                        </div>
                        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-between align-items-sm-center">
                            <strong class="fs-4"><?= sisonke_money($campaign['campaign_price']) ?></strong>
                            <a class="st-btn" href="<?= sisonke_e(SISONKE_BASE_URL) ?>/pages/campaign_detail.php?id=<?= (int) $campaign['campaign_id'] ?>"><?= sisonke_e(sisonke_t('marketplace_view_deal')) ?></a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php
require_once dirname(__DIR__) . '/includes/footer.php';
