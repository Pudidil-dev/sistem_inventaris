<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\BarangRepository;
use App\Repositories\LoginAttemptRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\BarangService;
use App\Services\KodeBarangGenerator;
use App\Services\UploadService;

// Perakit service beserta dependensinya untuk dipakai controller

function make_auth_service(): AuthService
{
    $pdo = Database::connection();

    return new AuthService(
        new UserRepository($pdo),
        new LoginAttemptRepository($pdo),
        (int) config('security.login_max_attempts', 5),
        (int) config('security.login_decay_minutes', 15),
    );
}

function make_upload_service(): UploadService
{
    return new UploadService(
        (string) config('upload.dir'),
        (int) config('upload.max_bytes'),
        (array) config('upload.mimes'),
    );
}

function make_kode_generator(): KodeBarangGenerator
{
    return new KodeBarangGenerator(new BarangRepository(Database::connection()));
}

function make_barang_service(): BarangService
{
    $repo = new BarangRepository(Database::connection());

    return new BarangService($repo, new KodeBarangGenerator($repo), make_upload_service());
}
