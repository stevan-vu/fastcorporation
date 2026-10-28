<?php

declare(strict_types=1);

namespace App\Security;

class Csrf {

    public const string FIELD = '_csrf';

    private const string SESSION_KEY = 'csrf_token';

    public function token(): string
    {
        return $_SESSION[self::SESSION_KEY] ??= bin2hex(random_bytes(32));
    }

    public function isValid(mixed $token): bool
    {
        $expected = $_SESSION[self::SESSION_KEY] ?? null;

        return \is_string($expected) && \is_string($token) && hash_equals($expected, $token);
    }
}
