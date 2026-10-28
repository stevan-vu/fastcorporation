<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\UserRepositoryInterface;

class Auth {

    private ?array $permissions = null;

    public function __construct(private UserRepositoryInterface $userRepository) {}

    public function userId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public function check(): bool
    {
        return $this->userId() !== null;
    }

    public function can(string $permission): bool
    {
        $this->permissions ??= $this->loadPermissions();

        return in_array($permission, $this->permissions, true);
    }

    public function login(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $this->permissions = null;
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        $this->permissions = null;
    }

    private function loadPermissions(): array
    {
        return $this->check() ? $this->userRepository->findPermissionNames($this->userId()) : [];
    }
}
