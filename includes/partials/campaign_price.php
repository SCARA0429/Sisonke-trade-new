<?php

declare(strict_types=1);

/** @var array $campaign */
/** @var string $priceClass */

$priceClass = $priceClass ?? 'st-campaign-price';
?>
<div class="<?= sisonke_e($priceClass) ?>">
    <?php if (sisonke_campaign_has_discount($campaign)): ?>
        <span class="st-price-was"><?= sisonke_money($campaign['campaign_price']) ?></span>
        <strong class="st-price-sale"><?= sisonke_money(sisonke_campaign_customer_price($campaign)) ?></strong>
        <span class="st-badge st-badge-green st-price-badge"><?= sisonke_e(sisonke_campaign_discount_label($campaign)) ?></span>
    <?php else: ?>
        <strong class="st-price-sale"><?= sisonke_money($campaign['campaign_price']) ?></strong>
    <?php endif; ?>
</div>
