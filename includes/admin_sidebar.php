<?php

declare(strict_types=1);

$adminActive = $adminActive ?? '';
$adminLinks = [
    'dashboard' => ['label' => 'Home', 'href' => SISONKE_BASE_URL . '/admin/dashboard.php'],
    'users' => ['label' => 'Users and RBAC', 'href' => SISONKE_BASE_URL . '/admin/users.php'],
    'transactions' => ['label' => 'Transactions', 'href' => SISONKE_BASE_URL . '/admin/transactions.php'],
    'disputes' => ['label' => 'Disputes', 'href' => SISONKE_BASE_URL . '/admin/disputes.php'],
];
?>
<aside class="admin-sidebar">
    <?php foreach ($adminLinks as $key => $link): ?>
        <a class="<?= $adminActive === $key ? 'is-active' : '' ?>" href="<?= sisonke_e($link['href']) ?>"><?= sisonke_e($link['label']) ?></a>
    <?php endforeach; ?>
</aside>
