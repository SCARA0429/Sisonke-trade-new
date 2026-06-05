<?php

declare(strict_types=1);

/**
 * Remove accidental test campaigns/products and apply demo polish on hosted DB.
 * Safe to run multiple times. Called from setup/run_migrations.php.
 */

if (!function_exists('sisonke_cleanup_production_data')) {
    function sisonke_cleanup_production_data(PDO $pdo): array
    {
        $allowedProducts = [
            '10KG Maize Meal',
            'School Shoes',
            'Grocery Mix',
        ];
        $placeholders = implode(', ', array_fill(0, count($allowedProducts), '?'));

        $deleteCampaigns = $pdo->prepare(
            "DELETE c FROM group_buy_campaigns c
             INNER JOIN products p ON c.product_id = p.product_id
             WHERE p.name NOT IN ({$placeholders})"
        );
        $deleteCampaigns->execute($allowedProducts);
        $campaignsRemoved = $deleteCampaigns->rowCount();

        $deleteProducts = $pdo->prepare(
            "DELETE FROM products WHERE name NOT IN ({$placeholders})"
        );
        $deleteProducts->execute($allowedProducts);
        $productsRemoved = $deleteProducts->rowCount();

        $discountStmt = $pdo->prepare(
            "UPDATE group_buy_campaigns c
             INNER JOIN products p ON c.product_id = p.product_id
             INNER JOIN users u ON c.seller_id = u.user_id
             SET c.discount_enabled = 1,
                 c.discount_type = 'percent',
                 c.discount_value = 10.00,
                 c.sale_price = 108.00
             WHERE u.email = 'seller@sisonke.test'
               AND p.name = 'School Shoes'
               AND c.status = 'active'"
        );
        $discountStmt->execute();
        $discountsApplied = $discountStmt->rowCount();

        return [
            'campaigns_removed' => $campaignsRemoved,
            'products_removed' => $productsRemoved,
            'discounts_applied' => $discountsApplied,
        ];
    }
}
