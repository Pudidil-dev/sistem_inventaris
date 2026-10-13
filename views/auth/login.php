<?php
/**
 * @var string      $username
 * @var string|null $error
 */
?>
<div class="row g-0 inv-auth-card shadow-lg overflow-hidden inv-animate">
    <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-center p-5 inv-auth-side text-white">
        <img src="<?= e(asset('img/hero-inventaris.svg')) ?>" alt="Ilustrasi rak inventaris sekolah"
             class="img-fluid mb-4 inv-float" width="480" height="320">
        <h2 class="h4 fw-bold">Kelola aset sekolah dengan rapi</h2>
        <p class="mb-0 opacity-75">Catat barang, pantau kondisi, dan temukan lokasi inventaris dalam hitungan detik.</p>
    </div>

    <div class="col-lg-6 bg-white p-4 p-md-5">
        <div class="text-center mb-4">
            <img src="<?= e(asset('img/logo.svg')) ?>" alt="Logo SI Inventaris" width="56" height="56" class="mb-2">
            <h1 class="h4 fw-bold mb-1">SI Inventaris Sekolah</h1>
            <p class="text-muted small mb-0">Silakan masuk untuk melanjutkan</p>
        </div>

        <?php if ($error !== null): ?>
            <div class="alert alert-danger d-flex gap-2 align-items-center py-2" role="alert" data-testid="login-error">
                <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form action="<?= e(url('login.php')) ?>" method="post" class="js-validate" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <div class="input-group has-validation">
                    <span class="input-group-text"><i class="bi bi-person" aria-hidden="true"></i></span>
                    <input type="text" class="form-control" id="username" name="username" value="<?= e($username) ?>"
                           required maxlength="50" autocomplete="username" autofocus
                           aria-describedby="username-feedback" data-msg="Username wajib diisi.">
                    <div class="invalid-feedback" id="username-feedback">Username wajib diisi.</div>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group has-validation">
                    <span class="input-group-text"><i class="bi bi-lock" aria-hidden="true"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required
                           autocomplete="current-password" aria-describedby="password-feedback"
                           data-msg="Password wajib diisi.">
                    <button class="btn btn-outline-secondary" type="button" data-toggle-password="#password"
                            aria-label="Tampilkan password" aria-pressed="false">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                    <div class="invalid-feedback" id="password-feedback">Password wajib diisi.</div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>Masuk
            </button>
        </form>
    </div>
</div>
