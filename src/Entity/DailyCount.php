<?php

declare(strict_types= 1);

namespace App\Entity;

use DateTimeImmutable;

class DailyCount{
    private DateTimeImmutable $day;
    private string $action;
    private int $count;

    public function __construct() {}
    public function getDay(): DateTimeImmutable
    {
        return $this->day;
    }
    public function setDay(DateTimeImmutable $day): void
    {
        $this->day = $day;
    }
    public function getAction(): string
    {
        return $this->action;
    }
    public function setAction(string $action): void
    {
        $this->action = $action;
    }
    public function getCount(): int
    {
        return $this->count;
    }
    public function setCount(int $count): void
    {
        $this->count = $count;
    }
}
