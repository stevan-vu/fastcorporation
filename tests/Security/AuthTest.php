<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\Auth;
use App\Tests\Fake\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

// Covers the read side of Auth; login()/logout() need a real session and are covered by manual/HTTP testing
class AuthTest extends TestCase {

    private InMemoryUserRepository $users;

    protected function setUp(): void
    {
        $_SESSION = [];
        $this->users = new InMemoryUserRepository();
        $this->users->rolePermissions = [
            'admin' => ['authentication', 'view_pages', 'view_pages_with_stats'],
            'user' => ['authentication', 'view_pages'],
        ];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testGuestIsNotLoggedInAndHasNoPermissions(): void
    {
        $auth = new Auth($this->users);

        $this->assertNull($auth->userId());
        $this->assertFalse($auth->check());
        $this->assertFalse($auth->can('view_pages'));
    }

    public function testUserHasOnlyTheirRolePermissions(): void
    {
        $_SESSION['user_id'] = $this->users->create('Ana', 'Test', 'ana@example.com', 'hash', 'user');
        $auth = new Auth($this->users);

        $this->assertTrue($auth->check());
        $this->assertTrue($auth->can('view_pages'));
        $this->assertFalse($auth->can('view_pages_with_stats'));
    }

    public function testAdminCanViewStats(): void
    {
        $_SESSION['user_id'] = $this->users->create('Admin', 'Test', 'admin@example.com', 'hash', 'admin');
        $auth = new Auth($this->users);

        $this->assertTrue($auth->can('view_pages_with_stats'));
    }
}
