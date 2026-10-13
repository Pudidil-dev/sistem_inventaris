<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

// Pengguna yang sudah login langsung ke dashboard
if (is_logged_in()) {
    redirect('index.php');
}

$username = '';
$error = null;

if (is_post()) {
    require_csrf();

    $username = input_string($_POST, 'username');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
        http_response_code(422);
    } else {
        $result = make_auth_service()->attempt($username, $password, client_ip());

        if ($result->ok) {
            login_user($result->user);
            set_flash('success', "Selamat datang, {$result->user['nama']}!", 'Login berhasil');
            redirect('index.php');
        }

        $error = $result->message;
        http_response_code(401);
    }
}

render('auth/login', [
    'pageTitle' => 'Masuk',
    'username'  => $username,
    'error'     => $error,
], 'auth');
