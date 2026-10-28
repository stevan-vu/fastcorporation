<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Core\Router\Router;
use App\Security\AccessGuard;
use App\Security\Auth;
use App\Security\Csrf;
use App\Tests\Fake\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

class AccessGuardTest extends TestCase {

    private InMemoryUserRepository $users;
    private Csrf $csrf;

    protected function setUp(): void
    {
        $_SESSION = [];
        $this->users = new InMemoryUserRepository();
        $this->users->rolePermissions = ['user' => ['authentication', 'view_pages']];
        $this->csrf = new Csrf();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    private function guard(): AccessGuard
    {
        return new AccessGuard(new Auth($this->users), $this->csrf);
    }

    private function logIn(): void
    {
        $_SESSION['user_id'] = $this->users->create('Ana', 'Test', 'ana@example.com', 'hash', 'user');
    }

    public function testPublicGetIsAllowedForGuests(): void
    {
        $this->assertNull($this->guard()->check('GET', Router::PUBLIC, []));
    }

    public function testPostWithoutTokenIsRejectedEvenOnPublicRoutes(): void
    {
        $response = $this->guard()->check('POST', Router::PUBLIC, []);

        $this->assertSame(403, $response?->status);
    }

    public function testPostWithValidTokenIsAllowed(): void
    {
        $token = $this->csrf->token();

        $this->assertNull($this->guard()->check('POST', Router::PUBLIC, [Csrf::FIELD => $token]));
    }

    public function testGuestIsRedirectedToLoginOnPrivateRoute(): void
    {
        $response = $this->guard()->check('GET', 'view_pages', []);

        $this->assertSame(303, $response?->status);
        $this->assertSame('/login', $response->headers['Location']);
    }

    public function testUserWithPermissionIsAllowed(): void
    {
        $this->logIn();

        $this->assertNull($this->guard()->check('GET', 'view_pages', []));
    }

    public function testUserWithoutPermissionGetsForbidden(): void
    {
        $this->logIn();

        $this->assertSame(403, $this->guard()->check('GET', 'view_pages_with_stats', [])?->status);
    }

    public function testCsrfIsCheckedBeforeLogin(): void
    {
        // A guest POST without a token gets 403, not a redirect that would hide the CSRF failure
        $this->assertSame(403, $this->guard()->check('POST', 'view_pages', [])?->status);
    }
}
