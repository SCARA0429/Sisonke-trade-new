<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_auth('admin');
require_once dirname(__DIR__) . '/includes/marketplace_service.php';

$currentAdminId = (int) $_SESSION['user_id'];
$permissions = sisonke_admin_permissions($pdo, $currentAdminId);

if (sisonke_is_post()) {
    sisonke_require_admin_capability($pdo, 'can_manage_users');
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_user') {
        $userId = (isset($_POST['user_id']) && $_POST['user_id'] !== '') ? (int) $_POST['user_id'] : null;
        $result = sisonke_save_admin_user($pdo, $userId, $_POST);
        sisonke_flash($result['success'] ? 'success' : 'danger', $result['message']);
    }

    if ($action === 'toggle_active') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId === $currentAdminId) {
            sisonke_flash('danger', 'You cannot suspend your own account.');
        } else {
            $stmt = $pdo->prepare('UPDATE users SET is_active = IF(is_active = 1, 0, 1) WHERE user_id = ?');
            $stmt->execute([$userId]);
            sisonke_flash('success', 'Account status updated.');
        }
    }

    if ($action === 'delete_user') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId === $currentAdminId) {
            sisonke_flash('danger', 'You cannot delete your own account.');
        } else {
            $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = ?');
            $stmt->execute([$userId]);
            sisonke_flash('success', 'User deleted.');
        }
    }

    if ($action === 'verify_seller') {
        $status = in_array(($_POST['verification_status'] ?? ''), ['pending', 'verified', 'rejected'], true)
            ? (string) $_POST['verification_status']
            : 'pending';
        $stmt = $pdo->prepare('UPDATE sellers SET verification_status = ? WHERE seller_id = ?');
        $stmt->execute([$status, (int) ($_POST['seller_id'] ?? 0)]);
        sisonke_flash('success', 'Seller verification updated.');
    }

    sisonke_redirect(SISONKE_BASE_URL . '/admin/users.php');
}

$users = sisonke_fetch_admin_users($pdo);
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editing = null;
foreach ($users as $user) {
    if ((int) $user['user_id'] === $editId) {
        $editing = $user;
        break;
    }
}

$profileValue = '';
if ($editing) {
    $profileValue = match ((string) $editing['role']) {
        'seller' => (string) ($editing['business_name'] ?? ''),
        'admin' => (string) ($editing['permission_level'] ?? 'support'),
        default => (string) ($editing['delivery_address'] ?? ''),
    };
}

$pageTitle = 'Users and RBAC';
$bodyClass = 'website-page st-page-dark';
$adminActive = 'users';
require_once dirname(__DIR__) . '/includes/header.php';
?>
<div class="admin-shell">
    <?php require dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>
    <section class="admin-content">
        <span class="st-kicker">RBAC</span>
        <h1 class="st-title mb-4">Users and roles</h1>

        <?php foreach (sisonke_take_flashes() as $flash): ?>
            <div class="alert alert-<?= sisonke_e($flash['type'] === 'success' ? 'success' : 'danger') ?>"><?= sisonke_e($flash['message']) ?></div>
        <?php endforeach; ?>

        <?php if (!$permissions['can_manage_users']): ?>
            <div class="alert alert-warning">Your admin permission level can view users but cannot make changes.</div>
        <?php endif; ?>

        <div class="st-grid st-grid-2">
            <article class="st-card">
                <div class="st-card-body">
                    <h2 class="st-card-title mb-3"><?= $editing ? 'Update user' : 'Create user' ?></h2>
                    <form method="post" action="<?= sisonke_e(SISONKE_BASE_URL) ?>/admin/users.php">
                        <input type="hidden" name="action" value="save_user">
                        <input type="hidden" name="user_id" value="<?= $editing ? (int) $editing['user_id'] : '' ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="st-label" for="full_name">Full name</label>
                                <input class="st-form-control" id="full_name" name="full_name" maxlength="120" required value="<?= sisonke_e($editing['full_name'] ?? '') ?>" <?= !$permissions['can_manage_users'] ? 'disabled' : '' ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="st-label" for="email">Email</label>
                                <input class="st-form-control" id="email" type="email" name="email" maxlength="255" required value="<?= sisonke_e($editing['email'] ?? '') ?>" <?= !$permissions['can_manage_users'] ? 'disabled' : '' ?>>
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label class="st-label" for="role">Role</label>
                                <select class="st-select js-role-select" id="role" name="role" <?= !$permissions['can_manage_users'] ? 'disabled' : '' ?>>
                                    <?php foreach (['buyer', 'seller', 'admin'] as $role): ?>
                                        <option value="<?= $role ?>" <?= (($editing['role'] ?? 'buyer') === $role) ? 'selected' : '' ?>><?= ucfirst($role) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="st-label" for="profile_value">Address/business</label>
                                <input class="st-form-control" id="profile_value" name="profile_value" value="<?= sisonke_e($profileValue) ?>" <?= !$permissions['can_manage_users'] ? 'disabled' : '' ?>>
                            </div>
                            <div class="col-md-4">
                                <label class="st-label" for="permission_level">Admin level</label>
                                <select class="st-select" id="permission_level" name="permission_level" <?= !$permissions['can_manage_users'] ? 'disabled' : '' ?>>
                                    <?php foreach (['super_admin', 'moderator', 'support'] as $level): ?>
                                        <option value="<?= $level ?>" <?= (($editing['permission_level'] ?? 'support') === $level) ? 'selected' : '' ?>><?= str_replace('_', ' ', ucfirst($level)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-8">
                                <label class="st-label" for="password">Password <?= $editing ? '(leave blank to keep current)' : '' ?></label>
                                <input class="st-form-control" id="password" type="password" name="password" minlength="6" <?= $editing ? '' : 'required' ?> <?= !$permissions['can_manage_users'] ? 'disabled' : '' ?>>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <label class="d-flex gap-2 align-items-center fw-bold">
                                    <input type="checkbox" name="is_active" value="1" <?= ((int) ($editing['is_active'] ?? 1) === 1) ? 'checked' : '' ?> <?= !$permissions['can_manage_users'] ? 'disabled' : '' ?>>
                                    Active
                                </label>
                            </div>
                        </div>
                        <div class="d-flex flex-column flex-md-row gap-2 mt-3">
                            <button class="st-btn st-btn-yellow" type="submit" <?= !$permissions['can_manage_users'] ? 'disabled' : '' ?>>Save User</button>
                            <?php if ($editing): ?>
                                <a class="st-btn st-btn-outline" href="<?= sisonke_e(SISONKE_BASE_URL) ?>/admin/users.php">Cancel Edit</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </article>

            <article class="st-card">
                <div class="st-card-body">
                    <h2 class="st-card-title mb-3">RBAC matrix</h2>
                    <div class="st-table-wrap">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Role</th>
                                    <th>Access</th>
                                    <th>Admin permissions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>Buyer</td><td>Marketplace, orders, delivery confirmation</td><td>None</td></tr>
                                <tr><td>Seller</td><td>Products, campaigns, sales view</td><td>None</td></tr>
                                <tr><td>Support</td><td>Admin read access</td><td>View queues</td></tr>
                                <tr><td>Moderator</td><td>Admin moderation</td><td>Resolve disputes</td></tr>
                                <tr><td>Super admin</td><td>Full admin</td><td>Manage users and RBAC</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </article>
        </div>

        <article class="st-card mt-4">
            <div class="st-card-body">
                <h2 class="st-card-title mb-3">All accounts</h2>
                <div class="st-table-wrap">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Profile</th>
                                <th>Status</th>
                                <th>Verification</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <?php
                                $userProfile = match ((string) $user['role']) {
                                    'seller' => (string) ($user['business_name'] ?? ''),
                                    'admin' => (string) ($user['permission_level'] ?? 'support'),
                                    default => (string) ($user['delivery_address'] ?? ''),
                                };
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= sisonke_e($user['full_name']) ?></strong><br>
                                        <span class="st-meta"><?= sisonke_e($user['email']) ?></span>
                                    </td>
                                    <td><span class="st-badge"><?= sisonke_e($user['role']) ?></span></td>
                                    <td><?= sisonke_e($userProfile) ?></td>
                                    <td><span class="st-badge <?= (bool) $user['is_active'] ? 'st-badge-green' : 'st-badge-muted' ?>"><?= (bool) $user['is_active'] ? 'active' : 'suspended' ?></span></td>
                                    <td>
                                        <?php if ($user['role'] === 'seller'): ?>
                                            <form class="d-flex gap-2" method="post" action="<?= sisonke_e(SISONKE_BASE_URL) ?>/admin/users.php">
                                                <input type="hidden" name="action" value="verify_seller">
                                                <input type="hidden" name="seller_id" value="<?= (int) $user['user_id'] ?>">
                                                <select class="st-select" name="verification_status" <?= !$permissions['can_manage_users'] ? 'disabled' : '' ?>>
                                                    <?php foreach (['pending', 'verified', 'rejected'] as $status): ?>
                                                        <option value="<?= $status ?>" <?= (($user['verification_status'] ?? 'pending') === $status) ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="st-btn st-btn-yellow" type="submit" <?= !$permissions['can_manage_users'] ? 'disabled' : '' ?>>Set</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="st-meta">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column flex-lg-row gap-2">
                                            <a class="st-btn st-btn-outline" href="<?= sisonke_e(SISONKE_BASE_URL) ?>/admin/users.php?edit=<?= (int) $user['user_id'] ?>">Edit</a>
                                            <form method="post" action="<?= sisonke_e(SISONKE_BASE_URL) ?>/admin/users.php">
                                                <input type="hidden" name="action" value="toggle_active">
                                                <input type="hidden" name="user_id" value="<?= (int) $user['user_id'] ?>">
                                                <button class="st-btn st-btn-outline" type="submit" <?= !$permissions['can_manage_users'] ? 'disabled' : '' ?>><?= (bool) $user['is_active'] ? 'Suspend' : 'Activate' ?></button>
                                            </form>
                                            <form method="post" action="<?= sisonke_e(SISONKE_BASE_URL) ?>/admin/users.php" onsubmit="return confirm('Delete this user and related records?');">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?= (int) $user['user_id'] ?>">
                                                <button class="st-btn" type="submit" <?= !$permissions['can_manage_users'] ? 'disabled' : '' ?>>Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </article>
    </section>
</div>
<?php
require_once dirname(__DIR__) . '/includes/footer.php';
