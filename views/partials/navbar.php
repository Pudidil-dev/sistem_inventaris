<?php
/** @var array|null $user */
$roleLabel = $user ? (\App\Enums\Role::tryFrom($user['role'])?->label() ?? $user['role']) : '';
?>
<nav class="navbar navbar-expand-lg navbar-dark inv-navbar sticky-top shadow-sm" aria-label="Navigasi utama">
    <div class="container-xl">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= e(url('index.php')) ?>">
            <img src="<?= e(asset('img/logo.svg')) ?>" alt="" width="32" height="32">
            <span>SI Inventaris</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarUtama"
                aria-controls="navbarUtama" aria-expanded="false" aria-label="Buka menu navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarUtama">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link<?= nav_active('/index.php') ?>" href="<?= e(url('index.php')) ?>">
                        <i class="bi bi-speedometer2 me-1" aria-hidden="true"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= nav_active('/barang/') ?>" href="<?= e(url('barang/index.php')) ?>">
                        <i class="bi bi-box-seam me-1" aria-hidden="true"></i>Data Barang
                    </a>
                </li>
                <?php if (is_admin()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle<?= nav_active('/kategori/', '/lokasi/') ?>" href="#" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false" data-testid="menu-master">
                            <i class="bi bi-database me-1" aria-hidden="true"></i>Master Data
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item<?= nav_active('/kategori/') ?>" href="<?= e(url('kategori/index.php')) ?>">
                                    <i class="bi bi-tags me-2" aria-hidden="true"></i>Kategori
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item<?= nav_active('/lokasi/') ?>" href="<?= e(url('lokasi/index.php')) ?>">
                                    <i class="bi bi-geo-alt me-2" aria-hidden="true"></i>Lokasi
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>

            <?php if ($user): ?>
                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle d-flex align-items-center gap-2" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false" data-testid="user-menu">
                        <i class="bi bi-person-circle" aria-hidden="true"></i>
                        <span><?= e($user['nama']) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text small text-muted">
                                Masuk sebagai <strong><?= e($roleLabel) ?></strong>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="<?= e(url('logout.php')) ?>" method="post">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Keluar
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
