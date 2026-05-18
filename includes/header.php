<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Sisonke Trade';
$bodyClass = $bodyClass ?? 'website-page';
require_once dirname(__DIR__) . '/config/db.php';
require_once __DIR__ . '/i18n.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$base = SISONKE_BASE_URL;
$baseHref = htmlspecialchars(str_replace(' ', '%20', $base), ENT_QUOTES, 'UTF-8');
$role = (string) ($_SESSION['user_role'] ?? 'guest');
$name = (string) ($_SESSION['user_name'] ?? '');
$assetVersion = filemtime(dirname(__DIR__) . '/assets/css/style.css') ?: time();
$currentLanguage = sisonke_current_language();
$languageOptions = sisonke_supported_languages();
$showLanguageSelector = $role !== 'admin';

$navLinks = [
    ['label' => sisonke_t('nav_marketplace'), 'href' => $baseHref . '/pages/campaigns.php'],
];

if ($role === 'buyer') {
    $navLinks[] = ['label' => sisonke_t('nav_my_orders'), 'href' => $baseHref . '/pages/dashboard.php'];
}
if ($role === 'seller') {
    $navLinks[] = ['label' => sisonke_t('nav_seller'), 'href' => $baseHref . '/seller/dashboard.php'];
    $navLinks[] = ['label' => sisonke_t('nav_products'), 'href' => $baseHref . '/seller/my_products.php'];
}
if ($role === 'admin') {
    $navLinks[] = ['label' => sisonke_t('nav_admin'), 'href' => $baseHref . '/admin/dashboard.php'];
    $navLinks[] = ['label' => sisonke_t('nav_users'), 'href' => $baseHref . '/admin/users.php'];
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(sisonke_html_language(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | Sisonke Trade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= $baseHref ?>/assets/css/style.css?v=<?= (int) $assetVersion ?>">
</head>
<body class="<?= htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') ?>">
<nav class="navbar navbar-expand-lg sisonke-navbar">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand sisonke-brand" href="<?= $baseHref ?>/pages/buyers1.php">Sisonke Trade</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="<?= htmlspecialchars(sisonke_t('toggle_navigation'), ENT_QUOTES, 'UTF-8') ?>">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <?php foreach ($navLinks as $link): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $link['href'] ?>"><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></a></li>
                <?php endforeach; ?>
                <?php if ($showLanguageSelector): ?>
                    <li class="nav-item">
                        <div class="sisonke-language-switcher" aria-label="<?= htmlspecialchars(sisonke_t('language_selector_label'), ENT_QUOTES, 'UTF-8') ?>">
                            <?php foreach ($languageOptions as $code => $language): ?>
                                <a class="<?= $currentLanguage === $code ? 'is-active' : '' ?>"
                                   href="<?= htmlspecialchars(sisonke_language_url($code), ENT_QUOTES, 'UTF-8') ?>"
                                   lang="<?= htmlspecialchars($language['html'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($language['short'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </li>
                <?php endif; ?>
                <?php if ($role === 'guest'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseHref ?>/pages/login.php"><?= htmlspecialchars(sisonke_t('nav_login'), ENT_QUOTES, 'UTF-8') ?></a></li>
                    <li class="nav-item"><a class="btn btn-sm sisonke-btn-yellow ms-lg-2" href="<?= $baseHref ?>/pages/register.php"><?= htmlspecialchars(sisonke_t('nav_register'), ENT_QUOTES, 'UTF-8') ?></a></li>
                <?php else: ?>
                    <li class="nav-item"><span class="nav-link sisonke-user-pill"><?= htmlspecialchars($name !== '' ? $name : ucfirst($role), ENT_QUOTES, 'UTF-8') ?></span></li>
                    <li class="nav-item">
                        <form method="post" action="<?= $baseHref ?>/api/logout.php" class="m-0">
                            <button class="btn btn-sm sisonke-btn-yellow" type="submit"><?= htmlspecialchars(sisonke_t('nav_logout'), ENT_QUOTES, 'UTF-8') ?></button>
                        </form>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="site-main">
