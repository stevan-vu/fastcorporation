<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use PDO;

class UserRepository implements UserRepositoryInterface {
    public function __construct(private PDO $pdo)
    {}
    public function findByEmail(string $email): ?User
    {
        $user = new User();

        $stmt = $this->pdo->prepare("SELECT id, email, password_hash FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $userRaw = $stmt->fetch(PDO::FETCH_OBJ);
        if ($userRaw === false) {
            return null;
        }
        $user->setId((int) $userRaw->id);
        $user->setEmail($userRaw->email);
        $user->setPasswordHash($userRaw->password_hash);
        
        return $user;
    }

    public function create(string $firstName, string $lastName, string $email, string $passwordHash, string $role = 'user'): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (role_id, first_name, last_name, email, password_hash)
             SELECT id, :first_name, :last_name, :email, :password_hash FROM roles WHERE name = :role"
        );
        
        $stmt->execute([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => $role,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new \RuntimeException("Role not found: $role");
        }

        return (int) $this->pdo->lastInsertId();
    }

    public function findAllEmails(): array
    {
        return $this->pdo->query('SELECT id, email FROM users ORDER BY email')->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function findPermissionNames(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.name FROM users u
             JOIN role_permissions rp ON rp.role_id = u.role_id
             JOIN permissions p ON p.id = rp.permission_id
             WHERE u.id = :id'
        );
        $stmt->execute(['id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
