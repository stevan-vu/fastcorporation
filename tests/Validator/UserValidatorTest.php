<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Tests\Fake\InMemoryUserRepository;
use App\Validator\UserValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UserValidatorTest extends TestCase {

    private InMemoryUserRepository $users;
    private UserValidator $validator;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->validator = new UserValidator($this->users);
    }

    public function testValidDataPasses(): void
    {
        $this->assertNull($this->validator->validateRegistration('Ana', 'Test', 'ana@example.com', 'longenough'));
    }

    public static function invalidRegistrations(): array
    {
        $long = str_repeat('a', 256);

        return [
            'missing first name' => ['', 'Test', 'ana@example.com', 'longenough', 'All fields are required.'],
            'missing password' => ['Ana', 'Test', 'ana@example.com', '', 'All fields are required.'],
            'name too long' => [$long, 'Test', 'ana@example.com', 'longenough', 'Fields must be at most 255 characters.'],
            'invalid email' => ['Ana', 'Test', 'ana@', 'longenough', 'Invalid email address.'],
            'short password' => ['Ana', 'Test', 'ana@example.com', 'short', 'Password must be at least 8 characters.'],
        ];
    }

    #[DataProvider('invalidRegistrations')]
    public function testInvalidDataReturnsError(string $firstName, string $lastName, string $email, string $password, string $expected): void
    {
        $this->assertSame($expected, $this->validator->validateRegistration($firstName, $lastName, $email, $password));
    }

    public function testPasswordOfExactlyEightCharactersPasses(): void
    {
        $this->assertNull($this->validator->validateRegistration('Ana', 'Test', 'ana@example.com', '12345678'));
    }

    public function testTakenEmailIsRejected(): void
    {
        $this->users->create('Ana', 'Test', 'ana@example.com', 'hash');

        $this->assertSame(
            'An account with this email already exists.',
            $this->validator->validateRegistration('Other', 'Person', 'ana@example.com', 'longenough'),
        );
    }
}
