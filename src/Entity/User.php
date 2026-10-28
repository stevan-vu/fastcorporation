<?php

declare(strict_types= 1);

namespace App\Entity;

class User{
    private int $id;
    private string $email;
    private string $passwordHash;

    public function __construct() {}
    public function getId(): int 
    {
        return $this->id;
    }
    public function setId(int $id): void 
    {
        $this->id = $id;
    }
    public function getEmail(): string 
    {
        return $this->email;
    }
    public function setEmail(string $email): void 
    {
        $this->email = $email;
    }
    public function getPasswordHash(): string 
    {
        return $this->passwordHash;
    }
    public function setPasswordHash(string $passwordHash): void 
    {
        $this->passwordHash = $passwordHash;
    }
}
