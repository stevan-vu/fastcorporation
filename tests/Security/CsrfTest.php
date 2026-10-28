<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\Csrf;
use PHPUnit\Framework\TestCase;

class CsrfTest extends TestCase {

    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testTokenIsRandomHexAndStableWithinSession(): void
    {
        $csrf = new Csrf();
        $token = $csrf->token();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
        $this->assertSame($token, $csrf->token());
        $this->assertSame($token, (new Csrf())->token());
    }

    public function testNewSessionGetsNewToken(): void
    {
        $first = (new Csrf())->token();
        $_SESSION = [];

        $this->assertNotSame($first, (new Csrf())->token());
    }

    public function testMatchingTokenIsValid(): void
    {
        $csrf = new Csrf();

        $this->assertTrue($csrf->isValid($csrf->token()));
    }

    public function testWrongMissingOrNonStringTokenIsInvalid(): void
    {
        $csrf = new Csrf();
        $csrf->token();

        $this->assertFalse($csrf->isValid('wrong'));
        $this->assertFalse($csrf->isValid(null));
        $this->assertFalse($csrf->isValid(''));
        $this->assertFalse($csrf->isValid(['array']));
    }

    public function testNothingIsValidBeforeATokenWasIssued(): void
    {
        $this->assertFalse((new Csrf())->isValid(''));
        $this->assertFalse((new Csrf())->isValid('anything'));
    }
}
