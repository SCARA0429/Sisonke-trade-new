<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_auth('seller');
require_once dirname(__DIR__) . '/includes/marketplace_service.php';
require_once dirname(__DIR__) . '/includes/i18n.php';

$sellerId = (int) $_SESSION['user_id'];
$productsUrl = SISONKE_BASE_URL . '/seller/my_products.php';

if (sisonke_is_post()) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create_product') {
        $result = sisonke_create_product($pdo, $sellerId, $_POST);
        if ($result['success']) {
            $productId = (int) ($result['product_id'] ?? 0);
            $nextAction = (string) ($_POST['next_action'] ?? 'launch');
            if ($nextAction === 'catalogue') {
                sisonke_flash('success', sisonke_t('product_saved_in_catalogue'));
                sisonke_redirect($productsUrl . '?saved=' . $productId);
            }

            sisonke_redirect($productsUrl . '?launch=' . $productId);
        }

        sisonke_flash('danger', $result['message']);
        sisonke_redirect($productsUrl);
    }

    if ($action === 'create_campaign') {
        $result = sisonke_create_campaign($pdo, $sellerId, $_POST, $_FILES);
        $launchId = (int) ($_POST['product_id'] ?? 0);
        sisonke_flash($result['success'] ? 'success' : 'danger', $result['message']);
        sisonke_redirect($result['success']
            ? SISONKE_BASE_URL . '/seller/dashboard.php'
            : $productsUrl . '?launch=' . $launchId);
    }

    if ($action === 'toggle_product') {
        $stmt = $pdo->prepare('UPDATE products SET is_active = IF(is_active = 1, 0, 1) WHERE product_id = ? AND seller_id = ?');
        $stmt->execute([(int) ($_POST['product_id'] ?? 0), $sellerId]);
        sisonke_flash('success', 'Product availability updated.');
    }

    sisonke_redirect($productsUrl);
}

$products = sisonke_fetch_seller_products($pdo, $sellerId);
$launchProductId = (int) ($_GET['launch'] ?? 0);
$savedProductId = (int) ($_GET['saved'] ?? 0);

if ($launchProductId <= 0 && (string) ($_GET['step'] ?? '') === 'campaign') {
    foreach ($products as $product) {
        if ((bool) $product['is_active']) {
            $launchProductId = (int) $product['product_id'];
            break;
        }
    }
}

$launchProduct = null;

foreach ($products as $product) {
    if ((int) $product['product_id'] === $launchProductId && (bool) $product['is_active']) {
        $launchProduct = $product;
        break;
    }
}

$isLaunchMode = $launchProduct !== null;
$highlightProductId = $isLaunchMode ? $launchProductId : $savedProductId;
$campaignFormState = sisonke_campaign_form_state($pdo, $sellerId, $launchProductId);
$pageTitle = sisonke_t('my_products_title');
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section class="st-hero-band">
    <span class="st-kicker"><?= sisonke_e(sisonke_t('seller_catalogue')) ?></span>
    <h1 class="st-title"><?= sisonke_e(sisonke_t('products_heading')) ?></h1>
    <p class="st-lede"><?= sisonke_e($isLaunchMode ? sisonke_t('launch_step_lede') : sisonke_t('products_lede')) ?></p>
</section>

<section class="st-page"<?= $highlightProductId > 0 ? ' data-highlight-product="' . $highlightProductId . '"' : '' ?>>
    <nav class="st-flow-progress mb-4" aria-label="<?= sisonke_e(sisonke_t('seller_setup_title')) ?>">
        <ol class="st-flow-progress-steps">
            <li class="st-flow-step<?= $isLaunchMode ? ' is-complete' : ' is-active' ?>">
                <span class="st-flow-step-num">1</span>
                <span class="st-flow-step-label"><?= sisonke_e(sisonke_t('seller_step_add_product')) ?></span>
            </li>
            <li class="st-flow-step<?= $isLaunchMode ? ' is-active' : '' ?>">
                <span class="st-flow-step-num">2</span>
                <span class="st-flow-step-label"><?= sisonke_e(sisonke_t('seller_step_launch_campaign')) ?></span>
            </li>
        </ol>
    </nav>

    <?php foreach (sisonke_take_flashes() as $flash): ?>
        <div class="alert alert-<?= sisonke_e($flash['type'] === 'success' ? 'success' : 'danger') ?>"><?= sisonke_e($flash['message']) ?></div>
    <?php endforeach; ?>

    <?php if ($savedProductId > 0 && !$isLaunchMode): ?>
        <?php
        $savedProduct = null;
        foreach ($products as $product) {
            if ((int) $product['product_id'] === $savedProductId) {
                $savedProduct = $product;
                break;
            }
        }
        ?>
        <?php if ($savedProduct !== null && (bool) $savedProduct['is_active']): ?>
            <div class="st-launch-banner mb-4">
                <div>
                    <strong><?= sisonke_e(sisonke_t('product_ready_for_campaign')) ?></strong>
                    <p class="st-meta mb-0"><?= sisonke_e((string) $savedProduct['name']) ?> — <?= sisonke_e(sisonke_t('launch_step_lede')) ?></p>
                </div>
                <a class="st-btn st-btn-yellow" href="<?= sisonke_e($productsUrl) ?>?launch=<?= (int) $savedProduct['product_id'] ?>"><?= sisonke_e(sisonke_t('continue_launch_campaign')) ?></a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="st-grid st-grid-2">
        <article class="st-card<?= $isLaunchMode ? ' st-card-launch' : '' ?>">
            <div class="st-card-body">
                <?php if ($isLaunchMode): ?>
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-2 mb-3">
                        <div>
                            <span class="st-kicker"><?= sisonke_e(sisonke_t('step_two_of_two')) ?></span>
                            <h2 class="st-card-title mb-1"><?= sisonke_e(sisonke_t('step_launch_campaign_now')) ?></h2>
                            <p class="st-meta mb-0"><?= sisonke_e(sisonke_t('launch_step_panel_lede')) ?></p>
                        </div>
                        <a class="st-btn st-btn-outline" href="<?= sisonke_e($productsUrl) ?>"><?= sisonke_e(sisonke_t('add_another_product')) ?></a>
                    </div>
                    <?php
                    $campaignFormAction = $productsUrl;
                    $campaignHiddenAction = 'create_campaign';
                    $lockProductSelect = true;
                    $formIdPrefix = 'launch-';
                    require __DIR__ . '/partials/campaign_form.php';
                    ?>
                <?php else: ?>
                    <span class="st-kicker"><?= sisonke_e(sisonke_t('step_one_of_two')) ?></span>
                    <h2 class="st-card-title mb-1"><?= sisonke_e(sisonke_t('add_product')) ?></h2>
                    <p class="st-meta mb-3"><?= sisonke_e(sisonke_t('add_product_step_lede')) ?></p>
                    <form method="post" action="<?= sisonke_e($productsUrl) ?>">
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
                            <label class="st-label" for="image_url"><?= sisonke_e(sisonke_t('product_image_label')) ?></label>
                            <input class="st-form-control" id="image_url" name="image_url" maxlength="255" placeholder="<?= sisonke_e(sisonke_t('image_url_placeholder')) ?>">
                            <small class="st-meta"><?= sisonke_e(sisonke_t('product_image_marketplace_help')) ?></small>
                        </div>
                        <div class="d-flex flex-column gap-2 mt-3">
                            <button class="st-btn st-btn-yellow" type="submit" name="next_action" value="launch"><?= sisonke_e(sisonke_t('save_and_launch_campaign')) ?></button>
                            <button class="st-btn st-btn-outline" type="submit" name="next_action" value="catalogue"><?= sisonke_e(sisonke_t('save_to_catalogue_only')) ?></button>
                        </div>
                    </form>
                <?php endif; ?>
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
                                    <?php $rowId = (int) $product['product_id']; ?>
                                    <tr id="product-<?= $rowId ?>" class="<?= $highlightProductId === $rowId ? 'is-highlighted' : '' ?>">
                                        <td>
                                            <strong><?= sisonke_e($product['name']) ?></strong><br>
                                            <span class="st-meta"><?= sisonke_e(sisonke_content_t($product['category'])) ?></span>
                                        </td>
                                        <td><?= sisonke_money($product['unit_price']) ?></td>
                                        <td><?= (int) $product['quantity_available'] ?></td>
                                        <td><?= (int) $product['campaign_count'] ?></td>
                                        <td><span class="st-badge <?= (bool) $product['is_active'] ? 'st-badge-green' : 'st-badge-muted' ?>"><?= sisonke_e((bool) $product['is_active'] ? sisonke_t('active') : sisonke_t('paused')) ?></span></td>
                                        <td>
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <?php if ((bool) $product['is_active']): ?>
                                                    <a class="st-btn st-btn-yellow" href="<?= sisonke_e($productsUrl) ?>?launch=<?= $rowId ?>"><?= sisonke_e(sisonke_t('create_campaign_for_product')) ?></a>
                                                <?php endif; ?>
                                                <form method="post" action="<?= sisonke_e($productsUrl) ?>">
                                                    <input type="hidden" name="action" value="toggle_product">
                                                    <input type="hidden" name="product_id" value="<?= $rowId ?>">
                                                    <button class="st-btn st-btn-outline" type="submit"><?= sisonke_e((bool) $product['is_active'] ? sisonke_t('pause') : sisonke_t('activate')) ?></button>
                                                </form>
                                            </div>
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
