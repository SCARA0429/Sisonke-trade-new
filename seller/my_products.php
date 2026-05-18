<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_auth('seller');
require_once dirname(__DIR__) . '/includes/marketplace_service.php';
require_once dirname(__DIR__) . '/includes/i18n.php';

$sellerId = (int) $_SESSION['user_id'];

if (sisonke_is_post()) {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'create_product') {
        $result = sisonke_create_product($pdo, $sellerId, $_POST);
        sisonke_flash($result['success'] ? 'success' : 'danger', $result['message']);
    }

    if ($action === 'toggle_product') {
        $stmt = $pdo->prepare('UPDATE products SET is_active = IF(is_active = 1, 0, 1) WHERE product_id = ? AND seller_id = ?');
        $stmt->execute([(int) ($_POST['product_id'] ?? 0), $sellerId]);
        sisonke_flash('success', 'Product availability updated.');
    }

    sisonke_redirect(SISONKE_BASE_URL . '/seller/my_products.php');
}

$products = sisonke_fetch_seller_products($pdo, $sellerId);
$pageTitle = sisonke_t('my_products_title');
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section class="st-hero-band">
    <span class="st-kicker"><?= sisonke_e(sisonke_t('seller_catalogue')) ?></span>
    <h1 class="st-title"><?= sisonke_e(sisonke_t('products_heading')) ?></h1>
    <p class="st-lede"><?= sisonke_e(sisonke_t('products_lede')) ?></p>
</section>

<section class="st-page">
    <?php foreach (sisonke_take_flashes() as $flash): ?>
        <div class="alert alert-<?= sisonke_e($flash['type'] === 'success' ? 'success' : 'danger') ?>"><?= sisonke_e($flash['message']) ?></div>
    <?php endforeach; ?>

    <div class="st-grid st-grid-2">
        <article class="st-card">
            <div class="st-card-body">
                <h2 class="st-card-title mb-3"><?= sisonke_e(sisonke_t('add_product')) ?></h2>
                <form method="post" action="<?= sisonke_e(SISONKE_BASE_URL) ?>/seller/my_products.php">
                    <input type="hidden" name="action" value="create_product">
                    <div class="mb-3">
                        <label class="st-label" for="name"><?= sisonke_e(sisonke_t('product_name')) ?></label>
                        <input class="st-form-control" id="name" name="name" maxlength="150" required>
                    </div>
                    <div class="mb-3">
                        <label class="st-label" for="category"><?= sisonke_e(sisonke_t('category')) ?></label>
                        <input class="st-form-control" id="category" name="category" maxlength="50" placeholder="<?= sisonke_e(sisonke_t('category_placeholder')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="st-label" for="description"><?= sisonke_e(sisonke_t('description')) ?></label>
                        <textarea class="st-textarea" id="description" name="description" required></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="st-label" for="unit_price"><?= sisonke_e(sisonke_t('unit_price')) ?></label>
                            <input class="st-form-control" id="unit_price" type="number" name="unit_price" step="0.01" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="st-label" for="quantity_available"><?= sisonke_e(sisonke_t('stock')) ?></label>
                            <input class="st-form-control" id="quantity_available" type="number" name="quantity_available" min="1" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="st-label" for="image_url"><?= sisonke_e(sisonke_t('image_url')) ?></label>
                        <input class="st-form-control" id="image_url" name="image_url" maxlength="255" placeholder="<?= sisonke_e(sisonke_t('image_url_placeholder')) ?>">
                    </div>
                    <button class="st-btn st-btn-yellow mt-3" type="submit"><?= sisonke_e(sisonke_t('save_product')) ?></button>
                </form>
            </div>
        </article>

        <article class="st-card">
            <div class="st-card-body">
                <h2 class="st-card-title mb-3"><?= sisonke_e(sisonke_t('current_catalogue')) ?></h2>
                <?php if ($products === []): ?>
                    <div class="st-empty"><?= sisonke_e(sisonke_t('no_products_saved')) ?></div>
                <?php else: ?>
                    <div class="st-table-wrap">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th><?= sisonke_e(sisonke_t('product')) ?></th>
                                    <th><?= sisonke_e(sisonke_t('price')) ?></th>
                                    <th><?= sisonke_e(sisonke_t('stock')) ?></th>
                                    <th><?= sisonke_e(sisonke_t('campaigns')) ?></th>
                                    <th><?= sisonke_e(sisonke_t('status')) ?></th>
                                    <th><?= sisonke_e(sisonke_t('table_action')) ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <strong><?= sisonke_e($product['name']) ?></strong><br>
                                            <span class="st-meta"><?= sisonke_e(sisonke_content_t($product['category'])) ?></span>
                                        </td>
                                        <td><?= sisonke_money($product['unit_price']) ?></td>
                                        <td><?= (int) $product['quantity_available'] ?></td>
                                        <td><?= (int) $product['campaign_count'] ?></td>
                                        <td><span class="st-badge <?= (bool) $product['is_active'] ? 'st-badge-green' : 'st-badge-muted' ?>"><?= sisonke_e((bool) $product['is_active'] ? sisonke_t('active') : sisonke_t('paused')) ?></span></td>
                                        <td>
                                            <form method="post" action="<?= sisonke_e(SISONKE_BASE_URL) ?>/seller/my_products.php">
                                                <input type="hidden" name="action" value="toggle_product">
                                                <input type="hidden" name="product_id" value="<?= (int) $product['product_id'] ?>">
                                                <button class="st-btn st-btn-outline" type="submit"><?= sisonke_e((bool) $product['is_active'] ? sisonke_t('pause') : sisonke_t('activate')) ?></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    </div>
</section>
<?php
require_once dirname(__DIR__) . '/includes/footer.php';
