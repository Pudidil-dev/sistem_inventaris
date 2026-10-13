<?php
/**
 * Layout tanpa navbar untuk halaman login / error tamu.
 * @var string $content
 * @var string $pageTitle
 */
$pageTitle = $pageTitle ?? 'Masuk';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="base-url" content="<?= e(url()) ?>">
    <title><?= e($pageTitle) ?> · <?= e(config('app.name')) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/logo.svg')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/sweetalert2/sweetalert2.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="inv-auth-bg">
    <main class="container py-4 py-md-5 d-flex flex-column justify-content-center min-vh-100">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-9 col-xl-8">
                <?php partial('flash'); ?>
                <?= $content ?>
            </div>
        </div>
    </main>

    <script src="<?= e(asset('vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
    <script src="<?= e(asset('vendor/sweetalert2/sweetalert2.min.js')) ?>"></script>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
    <script src="<?= e(asset('js/form-validation.js')) ?>"></script>
</body>
</html>
