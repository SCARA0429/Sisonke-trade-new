<?php

declare(strict_types=1);

require_once __DIR__ . '/i18n.php';

$base = SISONKE_BASE_URL;
$baseHref = htmlspecialchars(str_replace(' ', '%20', $base), ENT_QUOTES, 'UTF-8');
?>
</main>
<footer class="site-footer">
    <div class="container-fluid px-3 px-lg-4 d-flex flex-column flex-md-row gap-2 justify-content-between">
        <small>&copy; <?= date('Y') ?> Sisonke Trade. <?= htmlspecialchars(sisonke_t('footer_built'), ENT_QUOTES, 'UTF-8') ?></small>
        <small><a href="<?= $baseHref ?>/pages/campaigns.php"><?= htmlspecialchars(sisonke_t('nav_marketplace'), ENT_QUOTES, 'UTF-8') ?></a> <span aria-hidden="true">/</span> <a href="<?= $baseHref ?>/admin/dashboard.php"><?= htmlspecialchars(sisonke_t('nav_admin'), ENT_QUOTES, 'UTF-8') ?></a></small>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="<?= $baseHref ?>/assets/js/main.js"></script>
</body>
</html>
