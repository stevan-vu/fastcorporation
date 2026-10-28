<?php

declare(strict_types= 1);

namespace App\Entity;

use DateTimeImmutable;

class Event{
    public const string LOGIN = 'login';
    public const string LOGOUT = 'logout';
    public const string REGISTRATION = 'registration';
    public const string VIEW_PAGE_A = 'view-page-a';
    public const string VIEW_PAGE_B = 'view-page-b';
    public const string CLICK_BUY_COW = 'click-buy-cow';
    public const string CLICK_DOWNLOAD = 'click-download';
    private int $id;
    private ?int $userId = null;
    private string $action;
    private DateTimeImmutable $createdAt;
    // Filled when the query joins users; null for guests and deleted users
    private ?string $userEmail = null;

    public function __construct() {}
    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): void
    {
        $this->id = $id;
    }
    public function getUserId(): ?int
    {
        return $this->userId;
    }
    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }
    public function getAction(): string
    {
        return $this->action;
    }
    public function setAction(string $action): void
    {
        $this->action = $action;
    }
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
    public function getUserEmail(): ?string
    {
        return $this->userEmail;
    }
    public function setUserEmail(?string $userEmail): void
    {
        $this->userEmail = $userEmail;
    }
}
