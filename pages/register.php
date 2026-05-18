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
    $role = (string) ($_POST['role'] ?? 'buyer');
    $extraField = (string) ($_POST['extra_field'] ?? '');

    if ($password !== $passwordConfirm) {
        $errors[] = sisonke_t('passwords_mismatch');
    }

    if ($errors === []) {
        $result = sisonke_register_user($pdo, $email, $password, $fullName, $role, $extraField);
        if ($result['success']) {
            header('Location: ' . SISONKE_BASE_URL . '/pages/login.php?registered=1');
            exit;
        }
        $errors[] = $result['message'];
    }
}

$pageTitle = sisonke_t('register_title');
require_once dirname(__DIR__) . '/includes/header.php';

$base = SISONKE_BASE_URL;
?>
<div class="container py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h4 mb-1 text-center"><?= htmlspecialchars(sisonke_t('register_heading'), ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="text-muted small text-center mb-4"><?= htmlspecialchars(sisonke_t('register_intro'), ENT_QUOTES, 'UTF-8') ?></p>

                    <?php foreach ($errors as $msg): ?>
                        <div class="alert alert-danger py-2 small" role="alert"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endforeach; ?>

                    <form method="post" action="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/pages/register.php">
                        <div class="mb-3">
                            <label for="reg-name" class="form-label"><?= htmlspecialchars(sisonke_t('full_name'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="text" class="form-control form-control-lg" id="reg-name" name="full_name" required maxlength="120"
                                   value="<?= htmlspecialchars((string) ($_POST['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="reg-email" class="form-label"><?= htmlspecialchars(sisonke_t('email'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="email" class="form-control form-control-lg" id="reg-email" name="email" required maxlength="255"
                                   value="<?= htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(sisonke_t('registering_as'), ENT_QUOTES, 'UTF-8') ?></label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="role" id="role-buyer" value="buyer" <?= (($_POST['role'] ?? 'buyer') === 'buyer') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="role-buyer"><?= htmlspecialchars(sisonke_t('buyer'), ENT_QUOTES, 'UTF-8') ?></label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="role" id="role-seller" value="seller" <?= (($_POST['role'] ?? '') === 'seller') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="role-seller"><?= htmlspecialchars(sisonke_t('seller'), ENT_QUOTES, 'UTF-8') ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="reg-extra" class="form-label"><?= htmlspecialchars(sisonke_t('delivery_or_business'), ENT_QUOTES, 'UTF-8') ?></label>
                            <textarea class="form-control" id="reg-extra" name="extra_field" rows="2" required maxlength="255" placeholder="<?= htmlspecialchars(sisonke_t('delivery_business_placeholder'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($_POST['extra_field'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                            <small class="text-muted"><?= htmlspecialchars(sisonke_t('delivery_business_help'), ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                        <div class="mb-3">
                            <label for="reg-password" class="form-label"><?= htmlspecialchars(sisonke_t('password'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="password" class="form-control form-control-lg" id="reg-password" name="password" required minlength="6" maxlength="128">
                            <small class="text-muted"><?= htmlspecialchars(sisonke_t('password_help'), ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                        <div class="mb-4">
                            <label for="reg-password2" class="form-label"><?= htmlspecialchars(sisonke_t('confirm_password'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="password" class="form-control form-control-lg" id="reg-password2" name="password_confirm" required minlength="6" maxlength="128">
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100"><?= htmlspecialchars(sisonke_t('nav_register'), ENT_QUOTES, 'UTF-8') ?></button>
                    </form>

                    <p class="text-center small text-muted mt-4 mb-0">
                        <?= htmlspecialchars(sisonke_t('already_have_account'), ENT_QUOTES, 'UTF-8') ?>
                        <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/pages/login.php"><?= htmlspecialchars(sisonke_t('login_title'), ENT_QUOTES, 'UTF-8') ?></a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
require_once dirname(__DIR__) . '/includes/footer.php';
