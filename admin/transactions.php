<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_auth('admin');
require_once dirname(__DIR__) . '/includes/marketplace_service.php';

if (sisonke_is_post() && ($_POST['action'] ?? '') === 'set_escrow_status') {
    sisonke_require_admin_capability($pdo, 'can_resolve_disputes');
    $status = in_array(($_POST['escrow_status'] ?? ''), ['held', 'released', 'refunded', 'disputed'], true)
        ? (string) $_POST['escrow_status']
        : 'held';
    $timestampColumn = match ($status) {
        'released' => ', released_at = NOW()',
        'refunded' => ', refunded_at = NOW()',
        default => '',
    };
    $stmt = $pdo->prepare("UPDATE escrow_payments SET status = ? {$timestampColumn} WHERE escrow_id = ?");
    $stmt->execute([$status, (int) ($_POST['escrow_id'] ?? 0)]);
    sisonke_flash('success', 'Escrow status updated.');
    sisonke_redirect(SISONKE_BASE_URL . '/admin/transactions.php');
}

$transactions = sisonke_fetch_admin_transactions($pdo);
$summary = sisonke_fetch_admin_summary($pdo);
$pageTitle = 'Transactions';
$bodyClass = 'website-page st-page-dark';
$adminActive = 'transactions';
require_once dirname(__DIR__) . '/includes/header.php';
?>
<div class="admin-shell">
    <?php require dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>
    <section class="admin-content">
        <span class="st-kicker">Payments and escrow</span>
        <h1 class="st-title mb-4">Transaction control</h1>

        <?php foreach (sisonke_take_flashes() as $flash): ?>
            <div class="alert alert-<?= sisonke_e($flash['type'] === 'success' ? 'success' : 'danger') ?>"><?= sisonke_e($flash['message']) ?></div>
        <?php endforeach; ?>

        <div class="st-grid st-grid-3 mb-4">
            <div class="st-metric st-metric-red">
                <span class="st-metric-label">Completed transactions</span>
                <span class="st-metric-value"><?= (int) $summary['completed_transactions'] ?></span>
            </div>
            <div class="st-metric st-metric-green">
                <span class="st-metric-label">Held in escrow</span>
                <span class="st-metric-value"><?= sisonke_money($summary['escrow_held']) ?></span>
            </div>
            <div class="st-metric">
                <span class="st-metric-label">Open disputes</span>
                <span class="st-metric-value"><?= (int) $summary['open_disputes'] ?></span>
            </div>
        </div>

        <article class="st-card">
            <div class="st-card-body">
                <h2 class="st-card-title mb-3">Escrow ledger</h2>
                <?php if ($transactions === []): ?>
                    <div class="st-empty">No transaction records have been created yet.</div>
                <?php else: ?>
                    <div class="st-table-wrap">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Buyer</th>
                                    <th>Seller</th>
                                    <th>Product</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                    <th>Escrow</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td>
                                            <strong><?= sisonke_e($transaction['reference_number']) ?></strong><br>
                                            <span class="st-meta"><?= sisonke_e(date('d M Y H:i', strtotime((string) $transaction['created_at']))) ?></span>
                                        </td>
                                        <td><?= sisonke_e($transaction['buyer_name']) ?></td>
                                        <td><?= sisonke_e($transaction['business_name']) ?></td>
                                        <td><?= sisonke_e($transaction['product_name'] ?? 'Campaign item') ?></td>
                                        <td><?= sisonke_money($transaction['amount']) ?></td>
                                        <td>
                                            <span class="st-badge"><?= sisonke_e($transaction['transaction_status']) ?></span><br>
                                            <span class="st-meta"><?= sisonke_e($transaction['payment_method']) ?></span>
                                        </td>
                                        <td><span class="st-badge <?= $transaction['escrow_status'] === 'released' ? 'st-badge-green' : '' ?>"><?= sisonke_e($transaction['escrow_status']) ?></span></td>
                                        <td>
                                            <form class="d-flex gap-2" method="post" action="<?= sisonke_e(SISONKE_BASE_URL) ?>/admin/transactions.php">
                                                <input type="hidden" name="action" value="set_escrow_status">
                                                <input type="hidden" name="escrow_id" value="<?= (int) ($transaction['escrow_id'] ?? 0) ?>">
                                                <select class="st-select" name="escrow_status">
                                                    <?php foreach (['held', 'released', 'refunded', 'disputed'] as $status): ?>
                                                        <option value="<?= $status ?>" <?= $transaction['escrow_status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="st-btn st-btn-yellow" type="submit">Set</button>
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
    </section>
</div>
<?php
require_once dirname(__DIR__) . '/includes/footer.php';
