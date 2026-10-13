<?php
declare(strict_types=1);

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;

final class SessionHelperTest extends TestCase
{
    private const ADMIN = ['id' => '1', 'username' => 'admin', 'nama' => 'Administrator Sarpras', 'role' => 'admin', 'password' => 'hash'];
    private const PETUGAS = ['id' => 2, 'username' => 'petugas', 'nama' => 'Petugas Inventaris', 'role' => 'petugas'];

    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testFlashIsReadOnlyOnce(): void
    {
        set_flash('success', 'Tersimpan', 'Berhasil');

        $this->assertSame(['type' => 'success', 'message' => 'Tersimpan', 'title' => 'Berhasil'], get_flash());
        $this->assertNull(get_flash());
    }

    public function testLoginStoresOnlySafeFields(): void
    {
        login_user(self::ADMIN);

        $this->assertTrue(is_logged_in());
        $this->assertSame(1, current_user_id());
        $this->assertSame(
            ['id' => 1, 'username' => 'admin', 'nama' => 'Administrator Sarpras', 'role' => 'admin'],
            current_user()
        );
        $this->assertArrayNotHasKey('password', current_user());
    }

    public function testRoleChecks(): void
    {
        login_user(self::PETUGAS);

        $this->assertTrue(has_role('petugas'));
        $this->assertTrue(has_role('admin', 'petugas'));
        $this->assertFalse(has_role('admin'));
        $this->assertFalse(is_admin());
    }

    public function testLogoutClearsSession(): void
    {
        login_user(self::ADMIN);
        logout_user();

        $this->assertFalse(is_logged_in());
        $this->assertNull(current_user_id());
        $this->assertFalse(has_role('admin'));
    }
}
