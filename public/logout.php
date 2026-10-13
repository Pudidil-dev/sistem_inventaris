<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

// Logout hanya via POST + CSRF agar tidak bisa dipicu tautan dari situs lain
require_method('POST');
require_csrf();

logout_user();
set_flash('success', 'Anda telah berhasil keluar.');
redirect('login.php');
