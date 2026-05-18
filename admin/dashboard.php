<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_auth('admin');
require_once dirname(__DIR__) . '/includes/marketplace_service.php';

$summary = sisonke_fetch_admin_summary($pdo);
$transactions = array_slice(sisonke_fetch_admin_transactions($pdo), 0, 5);
$disputes = array_slice(sisonke_fetch_admin_disputes($pdo), 0, 4);
$pageTitle = 'Admin dashboard';
$bodyClass = 'website-page st-page-dark';
$adminActive = 'dashboard';
require_once dirname(__DIR__) . '/includes/header.php';
?>
<div class="admin-shell">
    <?php require dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>
    <section class="admin-content">
        <span class="st-kicker">Admin portal</span>
        <h1 class="st-title mb-4">Dashboard overview</h1>

        <div class="st-grid st-grid-4 mb-4">
            <div class="st-metric st-metric-red">
                <span class="st-metric-label">Total users</span>
                <span class="st-metric-value"><?= (int) $summary['total_users'] ?></span>
            </div>
            <div class="st-metric">
                <span class="st-metric-label">Live campaigns</span>
                <span class="st-metric-value"><?= (int) $summary['live_campaigns'] ?></span>
            </div>
            <div class="st-metric st-metric-green">
                <span class="st-metric-label">Escrow held</span>
                <span class="st-metric-value"><?= sisonke_money($summary['escrow_held']) ?></span>
            </div>
            <div class="st-metric">
                <span class="st-metric-label">Open disputes</span>
                <span class="st-metric-value"><?= (int) $summary['open_disputes'] ?></span>
            </div>
        </div>

        <div class="st-grid st-grid-2">
            <article class="st-card st-card-dark">
                <div class="st-card-body">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <h2 class="st-card-title mb-0">Recent transactions</h2>
                        <a class="st-btn st-btn-outline" href="<?= sisonke_e(SISONKE_BASE_URL) ?>/admin/transactions.php">View all</a>
                    </div>
                    <?php if ($transactions === []): ?>
                        <div class="st-empty">No transactions yet.</div>
                    <?php else: ?>
                        <div class="st-table-wrap">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Buyer</th>
                                        <th>Value</th>
                                        <th>Escrow</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td><?= sisonke_e($transaction['reference_number']) ?></td>
                                            <td><?= sisonke_e($transaction['buyer_name']) ?></td>
                                            <td><?= sisonke_money($transaction['amount']) ?></td>
                                            <td><span class="st-badge"><?= sisonke_e($transaction['escrow_status']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </article>

            <article class="st-card st-card-dark">
                <div class="st-card-body">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <h2 class="st-card-title mb-0">Dispute queue</h2>
                        <a class="st-btn st-btn-outline" href="<?= sisonke_e(SISONKE_BASE_URL) ?>/admin/disputes.php">Moderate</a>
                    </div>
                    <?php if ($disputes === []): ?>
                        <div class="st-empty">No open disputes.</div>
                    <?php else: ?>
                        <?php foreach ($disputes as $dispute): ?>
                            <div class="border-bottom border-secondary py-2">
                                <strong><?= sisonke_e($dispute['reason']) ?></strong>
                                <div class="st-meta"><?= sisonke_e($dispute['buyer_name'] ?? 'Buyer') ?> vs <?= sisonke_e($dispute['business_name'] ?? 'Seller') ?></div>
                                <span class="st-badge mt-2"><?= sisonke_e($dispute['status']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </article>
        </div>
    </section>
</div>
<?php
require_once dirname(__DIR__) . '/includes/footer.php';
