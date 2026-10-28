<?php

declare(strict_types=1);

namespace App\Tests\Fake;

use App\Entity\User;
use App\Repository\UserRepositoryInterface;

// Stand-in for the PDO repository so services can be tested without MySQL
class InMemoryUserRepository implements UserRepositoryInterface {

    /** @var array<int, array{firstName: string, lastName: string, email: string, passwordHash: string, role: string}> */
    public array $users = [];

    /** @var array<string, string[]> role => permission names */
    public array $rolePermissions = [];

    private int $nextId = 1;

    public function findByEmail(string $email): ?User
    {
        foreach ($this->users as $id => $row) {
            if ($row['email'] === $email) {
                $user = new User();
                $user->setId($id);
                $user->setEmail($row['email']);
                $user->setPasswordHash($row['passwordHash']);

                return $user;
            }
        }

        return null;
    }

    public function create(string $firstName, string $lastName, string $email, string $passwordHash, string $role = 'user'): int
    {
        $id = $this->nextId++;
        $this->users[$id] = compact('firstName', 'lastName', 'email', 'passwordHash', 'role');

        return $id;
    }

    public function findAllEmails(): array
    {
        $emails = array_column($this->users, 'email');
        $emails = array_combine(array_keys($this->users), $emails);
        asort($emails);

        return $emails;
    }

    public function findPermissionNames(int $userId): array
    {
        $role = $this->users[$userId]['role'] ?? null;

        return $role === null ? [] : ($this->rolePermissions[$role] ?? []);
    }
}
