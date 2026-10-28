<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;

interface UserRepositoryInterface {
    public function findByEmail(string $email): ?User;

    // Returns the new user's id
    public function create(string $firstName, string $lastName, string $email, string $passwordHash, string $role = 'user'): int;

    // id => email, for filter dropdowns
    public function findAllEmails(): array;

    // Permission names granted to the user through their role
    public function findPermissionNames(int $userId): array;
}
