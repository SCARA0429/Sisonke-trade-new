<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_service.php';
require_once dirname(__DIR__) . '/includes/i18n.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = (string) ($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
    $fullName = (string) ($_POST['full_name'] ?? '');

    if ($password !== $passwordConfirm) {
        $errors[] = sisonke_t('passwords_mismatch');
    }

    if ($errors === []) {
        $result = sisonke_register_user($pdo, $email, $password, $fullName);
        if ($result['success']) {
            header('Location: ' . SISONKE_BASE_URL . '/pages/login.php?registered=1');
            exit;
        }
        $errors[] = $result['message'];
    }
}

$pageTitle = sisonke_t('register_title');
require_once dirname(__DIR__) . '/includes/header.php';

$base = htmlspecialchars(SISONKE_BASE_URL, ENT_QUOTES, 'UTF-8');
?>
<section class="auth-page auth-page-register">
    <div class="auth-shell">
        <div class="auth-intro">
            <span class="st-kicker">Create account</span>
            <h1><?= htmlspecialchars(sisonke_t('register_heading'), ENT_QUOTES, 'UTF-8') ?></h1>
            <p><?= htmlspecialchars(sisonke_t('register_intro'), ENT_QUOTES, 'UTF-8') ?></p>
            <div class="auth-proof">
                <span><?= htmlspecialchars(sisonke_t('register_proof_buy'), ENT_QUOTES, 'UTF-8') ?></span>
                <span><?= htmlspecialchars(sisonke_t('register_proof_sell'), ENT_QUOTES, 'UTF-8') ?></span>
                <span><?= htmlspecialchars(sisonke_t('register_proof_escrow'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>

        <div class="auth-panel">
            <h2><?= htmlspecialchars(sisonke_t('nav_register'), ENT_QUOTES, 'UTF-8') ?></h2>

            <?php foreach ($errors as $msg): ?>
                <div class="st-alert st-alert-danger" role="alert"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>

            <p class="auth-helper-note"><?= htmlspecialchars(sisonke_t('register_c2c_note'), ENT_QUOTES, 'UTF-8') ?></p>

            <form method="post" action="<?= $base ?>/pages/register.php" class="auth-form">
                <div class="auth-field">
                    <label for="reg-name"><?= htmlspecialchars(sisonke_t('full_name'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="text" id="reg-name" name="full_name" required maxlength="120"
                           value="<?= htmlspecialchars((string) ($_POST['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="auth-field">
                    <label for="reg-email"><?= htmlspecialchars(sisonke_t('email'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="email" id="reg-email" name="email" required maxlength="255"
                           value="<?= htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="auth-field">
                    <label for="reg-password"><?= htmlspecialchars(sisonke_t('password'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="password" id="reg-password" name="password" required minlength="6" maxlength="128">
                    <small><?= htmlspecialchars(sisonke_t('password_help'), ENT_QUOTES, 'UTF-8') ?></small>
                </div>
                <div class="auth-field">
                    <label for="reg-password2"><?= htmlspecialchars(sisonke_t('confirm_password'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="password" id="reg-password2" name="password_confirm" required minlength="6" maxlength="128">
                </div>
                <button type="submit" class="auth-submit"><?= htmlspecialchars(sisonke_t('nav_register'), ENT_QUOTES, 'UTF-8') ?></button>
            </form>

            <p class="auth-switch">
                <?= htmlspecialchars(sisonke_t('already_have_account'), ENT_QUOTES, 'UTF-8') ?>
                <a href="<?= $base ?>/pages/login.php"><?= htmlspecialchars(sisonke_t('login_title'), ENT_QUOTES, 'UTF-8') ?></a>
            </p>
        </div>
    </div>
</section>
<?php
require_once dirname(__DIR__) . '/includes/footer.php';
