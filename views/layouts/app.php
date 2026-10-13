<?php
/**
 * Layout utama (setelah login).
 * @var string $content     HTML halaman yang sudah dirender
 * @var string $pageTitle
 * @var array  $pageStyles  path CSS relatif terhadap assets/
 * @var array  $pageScripts path JS relatif terhadap assets/
 */
$pageTitle = $pageTitle ?? 'Dashboard';
$pageStyles = $pageStyles ?? [];
$pageScripts = $pageScripts ?? [];
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
    <?php foreach ($pageStyles as $style): ?>
        <link rel="stylesheet" href="<?= e(asset($style)) ?>">
    <?php endforeach; ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <a class="visually-hidden-focusable position-absolute top-0 start-0 m-2 btn btn-light" href="#konten-utama">Lewati ke konten utama</a>

    <?php partial('navbar', ['user' => current_user()]); ?>

    <main id="konten-utama" class="flex-grow-1 py-4">
        <div class="container-xl">
            <?php partial('flash'); ?>
            <?= $content ?>
        </div>
    </main>

    <?php partial('footer'); ?>

    <script src="<?= e(asset('vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
    <script src="<?= e(asset('vendor/axios/axios.min.js')) ?>"></script>
    <script src="<?= e(asset('vendor/sweetalert2/sweetalert2.min.js')) ?>"></script>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
    <script src="<?= e(asset('js/form-validation.js')) ?>"></script>
    <script src="<?= e(asset('js/confirm-delete.js')) ?>"></script>
    <?php foreach ($pageScripts as $script): ?>
        <script src="<?= e(asset($script)) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
