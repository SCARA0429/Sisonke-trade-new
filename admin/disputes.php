<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_auth('admin');
require_once dirname(__DIR__) . '/includes/marketplace_service.php';

if (sisonke_is_post()) {
    sisonke_require_admin_capability($pdo, 'can_resolve_disputes');
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update_dispute') {
        $status = in_array(($_POST['status'] ?? ''), ['open', 'reviewing', 'resolved', 'rejected'], true)
            ? (string) $_POST['status']
            : 'reviewing';
        $resolvedSql = in_array($status, ['resolved', 'rejected'], true) ? ', resolved_at = NOW()' : ', resolved_at = NULL';
        $stmt = $pdo->prepare("UPDATE disputes SET status = ?, resolution_note = ? {$resolvedSql} WHERE dispute_id = ?");
        $stmt->execute([$status, trim((string) ($_POST['resolution_note'] ?? '')), (int) ($_POST['dispute_id'] ?? 0)]);
        sisonke_flash('success', 'Dispute updated.');
    }

    if ($action === 'create_dispute') {
        $transactionId = (int) ($_POST['transaction_id'] ?? 0);
        $stmt = $pdo->prepare(
            'SELECT transaction_id, escrow_id, participant_id, buyer_id, seller_id
             FROM transactions WHERE transaction_id = ? LIMIT 1'
        );
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch();

        if ($transaction && !empty($transaction['participant_id'])) {
            $stmt = $pdo->prepare('SELECT campaign_id FROM campaign_participants WHERE participant_id = ? LIMIT 1');
            $stmt->execute([(int) $transaction['participant_id']]);
            $campaignId = (int) ($stmt->fetchColumn() ?: 0);

            $stmt = $pdo->prepare(
                'INSERT INTO disputes (participant_id, campaign_id, buyer_id, seller_id, reason, details, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                (int) $transaction['participant_id'],
                $campaignId ?: null,
                (int) $transaction['buyer_id'],
                (int) $transaction['seller_id'],
                trim((string) ($_POST['reason'] ?? 'Admin review')),
                trim((string) ($_POST['details'] ?? '')),
                'open',
            ]);

            $stmt = $pdo->prepare("UPDATE escrow_payments SET status = 'disputed' WHERE escrow_id = ?");
            $stmt->execute([(int) $transaction['escrow_id']]);
            sisonke_flash('success', 'Dispute opened and escrow marked as disputed.');
        } else {
            sisonke_flash('danger', 'Choose a transaction linked to a buyer order.');
        }
    }

    sisonke_redirect(SISONKE_BASE_URL . '/admin/disputes.php');
}

$disputes = sisonke_fetch_admin_disputes($pdo);
$transactions = array_filter(
    sisonke_fetch_admin_transactions($pdo),
    static fn (array $transaction): bool => !empty($transaction['participant_id'])
);
$pageTitle = 'Disputes';
$bodyClass = 'website-page st-page-dark';
$adminActive = 'disputes';
require_once dirname(__DIR__) . '/includes/header.php';
?>
<div class="admin-shell">
    <?php require dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>
    <section class="admin-content">
        <span class="st-kicker">Moderation</span>
        <h1 class="st-title mb-4">Dispute queue</h1>

        <?php foreach (sisonke_take_flashes() as $flash): ?>
            <div class="alert alert-<?= sisonke_e($flash['type'] === 'success' ? 'success' : 'danger') ?>"><?= sisonke_e($flash['message']) ?></div>
        <?php endforeach; ?>

        <div class="st-grid st-grid-2">
            <article class="st-card">
                <div class="st-card-body">
                    <h2 class="st-card-title mb-3">Open dispute</h2>
                    <form method="post" action="<?= sisonke_e(SISONKE_BASE_URL) ?>/admin/disputes.php">
                        <input type="hidden" name="action" value="create_dispute">
                        <div class="mb-3">
                            <label class="st-label" for="transaction_id">Transaction</label>
                            <select class="st-select" id="transaction_id" name="transaction_id" required>
                                <?php foreach ($transactions as $transaction): ?>
                                    <option value="<?= (int) $transaction['transaction_id'] ?>">
                                        <?= sisonke_e($transaction['reference_number']) ?> - <?= sisonke_e($transaction['buyer_name']) ?> - <?= sisonke_money($transaction['amount']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="st-label" for="reason">Reason</label>
                            <input class="st-form-control" id="reason" name="reason" maxlength="255" value="Delivery or product quality review" required>
                        </div>
                        <div class="mb-3">
                            <label class="st-label" for="details">Details</label>
                            <textarea class="st-textarea" id="details" name="details"></textarea>
                        </div>
                        <button class="st-btn st-btn-yellow" type="submit" <?= $transactions === [] ? 'disabled' : '' ?>>Create Dispute</button>
                    </form>
                </div>
            </article>

            <article class="st-card">
                <div class="st-card-body">
                    <h2 class="st-card-title mb-3">Resolution workflow</h2>
                    <p>Admin RBAC separates queue visibility from moderation actions. Support admins can view this page, moderators can update cases, and super admins can manage all users and disputes.</p>
                    <p class="st-meta mb-0">When a dispute is opened, the linked demo escrow can be held as disputed until an admin resolves or rejects the case.</p>
                </div>
            </article>
        </div>

        <article class="st-card mt-4">
            <div class="st-card-body">
                <h2 class="st-card-title mb-3">Cases</h2>
                <?php if ($disputes === []): ?>
                    <div class="st-empty">No disputes have been logged.</div>
                <?php else: ?>
                    <div class="st-table-wrap">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Case</th>
                                    <th>Buyer</th>
                                    <th>Seller</th>
                                    <th>Product</th>
                                    <th>Status</th>
                                    <th>Resolution</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($disputes as $dispute): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?= (int) $dispute['dispute_id'] ?> <?= sisonke_e($dispute['reason']) ?></strong><br>
                                            <span class="st-meta"><?= sisonke_e($dispute['reference_number'] ?? 'No reference') ?></span>
                                        </td>
                                        <td><?= sisonke_e($dispute['buyer_name'] ?? 'Buyer') ?></td>
                                        <td><?= sisonke_e($dispute['business_name'] ?? 'Seller') ?></td>
                                        <td><?= sisonke_e($dispute['product_name'] ?? 'Campaign item') ?></td>
                                        <td><span class="st-badge <?= $dispute['status'] === 'resolved' ? 'st-badge-green' : '' ?>"><?= sisonke_e($dispute['status']) ?></span></td>
                                        <td>
                                            <form class="d-grid gap-2" method="post" action="<?= sisonke_e(SISONKE_BASE_URL) ?>/admin/disputes.php">
                                                <input type="hidden" name="action" value="update_dispute">
                                                <input type="hidden" name="dispute_id" value="<?= (int) $dispute['dispute_id'] ?>">
                                                <select class="st-select" name="status">
                                                    <?php foreach (['open', 'reviewing', 'resolved', 'rejected'] as $status): ?>
                                                        <option value="<?= $status ?>" <?= $dispute['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <textarea class="st-textarea" name="resolution_note" placeholder="Resolution note"><?= sisonke_e($dispute['resolution_note'] ?? '') ?></textarea>
                                                <button class="st-btn st-btn-yellow" type="submit">Save Case</button>
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
