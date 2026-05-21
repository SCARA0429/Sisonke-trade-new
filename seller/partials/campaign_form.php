<?php

declare(strict_types=1);

/** @var array $campaignFormState */
/** @var string $campaignFormAction */
/** @var bool $lockProductSelect */
/** @var string $formIdPrefix */

$lockProductSelect = $lockProductSelect ?? false;
$formIdPrefix = $formIdPrefix ?? '';
$products = $campaignFormState['products'];
$preselectProductId = (int) $campaignFormState['preselect_product_id'];
$prefillCampaignPrice = (string) $campaignFormState['prefill_campaign_price'];
$selectedProduct = $campaignFormState['selected_product'] ?? null;
$productImageUrl = is_array($selectedProduct) ? trim((string) ($selectedProduct['image_url'] ?? '')) : '';

if ($products === []): ?>
    <div class="st-empty">
        <p class="mb-2"><?= sisonke_e(sisonke_t('campaign_catalogue_hint')) ?></p>
        <p class="mb-0">
            <?= sisonke_e(sisonke_t('add_active_product')) ?>
            <a href="<?= sisonke_e(SISONKE_BASE_URL) ?>/seller/my_products.php"><?= sisonke_e(sisonke_t('go_to_products')) ?></a>.
        </p>
    </div>
<?php else: ?>
    <form method="post" action="<?= sisonke_e($campaignFormAction) ?>">
        <?php if (!empty($campaignHiddenAction)): ?>
            <input type="hidden" name="action" value="<?= sisonke_e((string) $campaignHiddenAction) ?>">
        <?php endif; ?>
        <?php if ($lockProductSelect && $selectedProduct !== null): ?>
            <input type="hidden" name="product_id" value="<?= (int) $selectedProduct['product_id'] ?>">
            <div class="st-product-pick mb-3">
                <span class="st-meta"><?= sisonke_e(sisonke_t('product')) ?></span>
                <strong class="d-block"><?= sisonke_e((string) $selectedProduct['name']) ?></strong>
                <span class="st-meta">
                    <?= sisonke_money($selectedProduct['unit_price']) ?>
                    &middot;
                    <?= (int) $selectedProduct['quantity_available'] ?> <?= sisonke_e(sisonke_t('in_stock')) ?>
                </span>
                <?php if ($productImageUrl !== ''): ?>
                    <img class="st-product-pick-image mt-2" src="<?= sisonke_e($productImageUrl) ?>" alt="">
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="mb-3">
                <label class="st-label" for="<?= sisonke_e($formIdPrefix) ?>product_id"><?= sisonke_e(sisonke_t('product')) ?></label>
                <select class="st-select" id="<?= sisonke_e($formIdPrefix) ?>product_id" name="product_id" required>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= (int) $product['product_id'] ?>"<?= (int) $product['product_id'] === $preselectProductId ? ' selected' : '' ?>>
                            <?= sisonke_e($product['name']) ?> - <?= sisonke_money($product['unit_price']) ?> - <?= (int) $product['quantity_available'] ?> <?= sisonke_e(sisonke_t('in_stock')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="st-label" for="<?= sisonke_e($formIdPrefix) ?>campaign_price"><?= sisonke_e(sisonke_t('campaign_price')) ?></label>
                <input class="st-form-control" id="<?= sisonke_e($formIdPrefix) ?>campaign_price" type="number" name="campaign_price" step="0.01" min="1" value="<?= $prefillCampaignPrice !== '' ? sisonke_e($prefillCampaignPrice) : '' ?>" required>
            </div>
            <div class="col-md-6">
                <label class="st-label" for="<?= sisonke_e($formIdPrefix) ?>deadline"><?= sisonke_e(sisonke_t('deadline')) ?></label>
                <input class="st-form-control" id="<?= sisonke_e($formIdPrefix) ?>deadline" type="datetime-local" name="deadline" required>
            </div>
        </div>
        <div class="row g-3 mt-1">
            <div class="col-md-4">
                <label class="st-label" for="<?= sisonke_e($formIdPrefix) ?>min_participants"><?= sisonke_e(sisonke_t('minimum_buyers')) ?></label>
                <input class="st-form-control" id="<?= sisonke_e($formIdPrefix) ?>min_participants" type="number" name="min_participants" min="1" value="5" required>
            </div>
            <div class="col-md-4">
                <label class="st-label" for="<?= sisonke_e($formIdPrefix) ?>max_participants"><?= sisonke_e(sisonke_t('maximum_units')) ?></label>
                <input class="st-form-control" id="<?= sisonke_e($formIdPrefix) ?>max_participants" type="number" name="max_participants" min="1" value="50" required>
            </div>
            <div class="col-md-4">
                <label class="st-label" for="<?= sisonke_e($formIdPrefix) ?>target_quantity"><?= sisonke_e(sisonke_t('target_units')) ?></label>
                <input class="st-form-control" id="<?= sisonke_e($formIdPrefix) ?>target_quantity" type="number" name="target_quantity" min="1" value="25" required>
            </div>
        </div>
        <p class="st-meta mb-0 mt-3">
            <?= sisonke_e($productImageUrl !== '' ? sisonke_t('campaign_uses_product_image') : sisonke_t('campaign_uses_product_image_missing')) ?>
            <?php if ($productImageUrl === ''): ?>
                <a href="<?= sisonke_e(SISONKE_BASE_URL) ?>/seller/my_products.php"><?= sisonke_e(sisonke_t('go_to_products')) ?></a>
            <?php endif; ?>
        </p>
        <button class="st-btn st-btn-yellow mt-3" type="submit"><?= sisonke_e(sisonke_t('launch_campaign')) ?></button>
    </form>
<?php endif; ?>
