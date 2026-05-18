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

$base = SISONKE_BASE_URL;
?>
<div class="login-page-container container py-4 py-md-5">
    <div class="login-form-row row justify-content-center">
        <div class="login-form-column col-12 col-md-10 col-lg-5">
            <div class="login-card card shadow-sm border-0">
                <div class="login-card-body card-body p-4 p-md-5">
                    <h1 class="h4 mb-1 text-center"><?= htmlspecialchars(sisonke_t('login_title'), ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="text-muted small text-center mb-4"><?= htmlspecialchars(sisonke_t('login_intro'), ENT_QUOTES, 'UTF-8') ?></p>

                    <div id="login-alert" class="login-error-message alert alert-danger py-2 small d-none" role="alert"></div>

                    <?php if ($registered): ?>
                        <div class="registration-success-message alert alert-success py-2 small" role="alert"><?= htmlspecialchars(sisonke_t('registration_success'), ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <?php if ($suspended): ?>
                        <div class="account-suspended-message alert alert-warning py-2 small" role="alert"><?= htmlspecialchars(sisonke_t('account_suspended'), ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <?php foreach ($errors as $msg): ?>
                        <div class="login-validation-message alert alert-danger py-2 small" role="alert"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endforeach; ?>

                    <form id="login-form"
                          method="post"
                          action="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/pages/login.php"
                          data-login-api="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/api/login.php"
                          class="needs-validation"
                          novalidate>
                        <div class="email-section mb-3">
                            <label for="login-email" class="form-label"><?= htmlspecialchars(sisonke_t('email'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="email"
                                   class="form-control form-control-lg"
                                   id="login-email"
                                   name="email"
                                   required
                                   maxlength="255"
                                   autocomplete="username"
                                   inputmode="email"
                                   value="<?= htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <div class="invalid-feedback"><?= htmlspecialchars(sisonke_t('invalid_email'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="password-section mb-3">
                            <label for="login-password" class="form-label"><?= htmlspecialchars(sisonke_t('password'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="password"
                                   class="form-control form-control-lg"
                                   id="login-password"
                                   name="password"
                                   required
                                   autocomplete="current-password">
                            <div class="invalid-feedback"><?= htmlspecialchars(sisonke_t('password_required'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <button type="submit" class="login-submit-button btn btn-primary btn-lg w-100" id="login-submit">
                            <span class="login-submit-label"><?= htmlspecialchars(sisonke_t('login_title'), ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="spinner-border spinner-border-sm ms-2 d-none login-submit-spinner" role="status" aria-hidden="true"></span>
                        </button>
                    </form>

                    <p class="register-link-section text-center small text-muted mt-4 mb-0">
                        <?= htmlspecialchars(sisonke_t('new_here'), ENT_QUOTES, 'UTF-8') ?>
                        <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/pages/register.php"><?= htmlspecialchars(sisonke_t('create_account'), ENT_QUOTES, 'UTF-8') ?></a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
require_once dirname(__DIR__) . '/includes/footer.php';
