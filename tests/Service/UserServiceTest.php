<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Exception\RegistrationValidationException;
use App\Service\UserService;
use App\Tests\Fake\InMemoryUserRepository;
use App\Validator\UserValidator;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase {

    private InMemoryUserRepository $users;
    private UserService $service;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->service = new UserService($this->users, new UserValidator($this->users));
    }

    public function testAuthenticateReturnsUserForCorrectPassword(): void
    {
        $id = $this->users->create('Ana', 'Test', 'ana@example.com', password_hash('secret123', PASSWORD_BCRYPT, ['cost' => 4]));

        $user = $this->service->authenticate('ana@example.com', 'secret123');

        $this->assertNotNull($user);
        $this->assertSame($id, $user->getId());
    }

    public function testAuthenticateReturnsNullForWrongPassword(): void
    {
        $this->users->create('Ana', 'Test', 'ana@example.com', password_hash('secret123', PASSWORD_BCRYPT, ['cost' => 4]));

        $this->assertNull($this->service->authenticate('ana@example.com', 'wrong'));
    }

    public function testAuthenticateReturnsNullForUnknownEmail(): void
    {
        $this->assertNull($this->service->authenticate('nobody@example.com', 'secret123'));
    }

    public function testRegisterCreatesUserWithHashedPasswordAndUserRole(): void
    {
        $id = $this->service->register('Ana', 'Test', 'ana@example.com', 'longenough');

        $stored = $this->users->users[$id];
        $this->assertSame('ana@example.com', $stored['email']);
        $this->assertSame('user', $stored['role']);
        $this->assertNotSame('longenough', $stored['passwordHash']);
        $this->assertTrue(password_verify('longenough', $stored['passwordHash']));
    }

    public function testRegisteredUserCanAuthenticate(): void
    {
        $id = $this->service->register('Ana', 'Test', 'ana@example.com', 'longenough');

        $this->assertSame($id, $this->service->authenticate('ana@example.com', 'longenough')?->getId());
    }

    public function testRegisterRejectsInvalidDataWithoutCreatingUser(): void
    {
        try {
            $this->service->register('Ana', 'Test', 'not-an-email', 'longenough');
            $this->fail('Expected ValidationException');
        } catch (RegistrationValidationException $e) {
            $this->assertSame('Invalid email address.', $e->getMessage());
        }

        $this->assertSame([], $this->users->users);
    }

    public function testRegisterRejectsTakenEmail(): void
    {
        $this->service->register('Ana', 'Test', 'ana@example.com', 'longenough');

        $this->expectException(RegistrationValidationException::class);
        $this->expectExceptionMessage('An account with this email already exists.');

        $this->service->register('Other', 'Person', 'ana@example.com', 'longenough');
    }

    public function testGetEmailsByIdIsSortedByEmail(): void
    {
        $zed = $this->users->create('Zed', 'Z', 'zed@example.com', 'hash');
        $amy = $this->users->create('Amy', 'A', 'amy@example.com', 'hash');

        $this->assertSame([$amy => 'amy@example.com', $zed => 'zed@example.com'], $this->service->getEmailsById());
    }
}
