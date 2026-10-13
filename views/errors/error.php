<?php
/**
 * @var int    $status
 * @var string $message
 */
$titles = [
    403 => 'Akses Ditolak',
    404 => 'Tidak Ditemukan',
    405 => 'Metode Tidak Diizinkan',
    419 => 'Sesi Kedaluwarsa',
    500 => 'Kesalahan Sistem',
];
$icons = [403 => 'shield-lock', 404 => 'search', 405 => 'slash-circle', 419 => 'hourglass-split', 500 => 'bug'];
$backUrl = url(is_logged_in() ? 'index.php' : 'login.php');
?>
<div class="row justify-content-center">
    <div class="col-md-10 col-lg-8">
        <div class="card inv-card text-center p-4 p-md-5 inv-animate" data-testid="error-page">
            <div class="inv-error-icon mx-auto mb-3">
                <i class="bi bi-<?= e($icons[$status] ?? 'exclamation-triangle') ?>" aria-hidden="true"></i>
            </div>
            <p class="display-5 fw-bold text-primary mb-0"><?= (int) $status ?></p>
            <h1 class="h4 fw-bold"><?= e($titles[$status] ?? 'Terjadi Kesalahan') ?></h1>
            <p class="text-muted"><?= e($message) ?></p>
            <div>
                <a href="<?= e($backUrl) ?>" class="btn btn-primary">
                    <i class="bi bi-house me-1" aria-hidden="true"></i><?= is_logged_in() ? 'Kembali ke Dashboard' : 'Ke Halaman Login' ?>
                </a>
            </div>
        </div>
    </div>
</div>
