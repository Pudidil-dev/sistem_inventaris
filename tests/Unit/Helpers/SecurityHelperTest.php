<?php
declare(strict_types=1);

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;

final class SecurityHelperTest extends TestCase
{
    private array $server;

    protected function setUp(): void
    {
        $_SESSION = [];
        $this->server = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_SERVER = $this->server;
    }

    public function testEscapeHtml(): void
    {
        $this->assertSame('&lt;script&gt;&quot;x&quot; &#039;y&#039; &amp;', e('<script>"x" \'y\' &'));
        $this->assertSame('', e(null));
        $this->assertSame('123', e(123));
    }

    public function testCsrfTokenIsStablePerSession(): void
    {
        $token = csrf_token();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        $this->assertSame($token, csrf_token());
        $this->assertStringContainsString('name="_token" value="' . $token . '"', csrf_field());
    }

    public function testVerifyCsrf(): void
    {
        $this->assertFalse(verify_csrf('apa-saja'), 'Tanpa token di sesi harus ditolak');

        $token = csrf_token();

        $this->assertTrue(verify_csrf($token));
        $this->assertFalse(verify_csrf('token-palsu'));
        $this->assertFalse(verify_csrf(null));
    }

    public function testLikeEscape(): void
    {
        $this->assertSame('50\\%\\_off\\\\', like_escape('50%_off\\'));
        $this->assertSame('proyektor', like_escape('proyektor'));
    }

    public function testClientIp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.20';
        $this->assertSame('192.168.1.20', client_ip());

        $_SERVER['REMOTE_ADDR'] = 'bukan-ip';
        $this->assertSame('0.0.0.0', client_ip());
    }
}
