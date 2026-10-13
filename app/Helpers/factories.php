<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\LoginAttemptRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;

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
