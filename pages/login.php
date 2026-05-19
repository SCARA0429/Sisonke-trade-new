<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth_service.php';
require_once dirname(__DIR__) . '/includes/i18n.php';

$errors = [];
$registered = isset($_GET['registered']);
$suspended = ($_GET['error'] ?? '') === 'account_suspended';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = (string) ($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $result = sisonke_verify_login($pdo, $email, $password);

    if ($result['success']) {
        sisonke_login_session(
            $result['user_id'],
            $result['role'],
            sisonke_normalize_email($email),
            $result['full_name'],
            true
        );
        header('Location: ' . sisonke_dashboard_path_for_role($result['role']));
        exit;
    }

    $errors[] = $result['message'];
}

$pageTitle = sisonke_t('login_title');
require_once dirname(__DIR__) . '/includes/header.php';

$base = htmlspecialchars(SISONKE_BASE_URL, ENT_QUOTES, 'UTF-8');
?>
<section class="auth-page">
    <div class="auth-shell">
        <div class="auth-intro">
            <span class="st-kicker">Sisonke Trade</span>
            <h1><?= htmlspecialchars(sisonke_t('login_title'), ENT_QUOTES, 'UTF-8') ?></h1>
            <p><?= htmlspecialchars(sisonke_t('login_intro'), ENT_QUOTES, 'UTF-8') ?></p>
            <div class="auth-proof">
                <span>Secure escrow</span>
                <span>Verified sellers</span>
                <span>Community deals</span>
            </div>
        </div>

        <div class="auth-panel">
            <h2><?= htmlspecialchars(sisonke_t('login_title'), ENT_QUOTES, 'UTF-8') ?></h2>

            <div id="login-alert" class="login-error-message st-alert st-alert-danger d-none" role="alert"></div>

            <?php if ($registered): ?>
                <div class="registration-success-message st-alert st-alert-success" role="alert"><?= htmlspecialchars(sisonke_t('registration_success'), ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($suspended): ?>
                <div class="account-suspended-message st-alert st-alert-warning" role="alert"><?= htmlspecialchars(sisonke_t('account_suspended'), ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php foreach ($errors as $msg): ?>
                <div class="login-validation-message st-alert st-alert-danger" role="alert"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>

            <form id="login-form"
                  method="post"
                  action="<?= $base ?>/pages/login.php"
                  data-login-api="<?= $base ?>/api/login.php"
                  class="auth-form needs-validation"
                  novalidate>
                <div class="auth-field">
                    <label for="login-email"><?= htmlspecialchars(sisonke_t('email'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="email"
                           id="login-email"
                           name="email"
                           required
                           maxlength="255"
                           autocomplete="username"
                           inputmode="email"
                           value="<?= htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="invalid-feedback"><?= htmlspecialchars(sisonke_t('invalid_email'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="auth-field">
                    <label for="login-password"><?= htmlspecialchars(sisonke_t('password'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="password"
                           id="login-password"
                           name="password"
                           required
                           autocomplete="current-password">
                    <div class="invalid-feedback"><?= htmlspecialchars(sisonke_t('password_required'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <button type="submit" class="auth-submit login-submit-button" id="login-submit">
                    <span class="login-submit-label"><?= htmlspecialchars(sisonke_t('login_title'), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="spinner-border spinner-border-sm ms-2 d-none login-submit-spinner" role="status" aria-hidden="true"></span>
                </button>
            </form>

            <p class="auth-switch">
                <?= htmlspecialchars(sisonke_t('new_here'), ENT_QUOTES, 'UTF-8') ?>
                <a href="<?= $base ?>/pages/register.php"><?= htmlspecialchars(sisonke_t('create_account'), ENT_QUOTES, 'UTF-8') ?></a>
            </p>
        </div>
    </div>
</section>
<?php
require_once dirname(__DIR__) . '/includes/footer.php';
