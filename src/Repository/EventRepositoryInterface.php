<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\DailyCount;
use App\Entity\Event;
use DateTimeImmutable;

interface EventRepositoryInterface {
    // Returns the new event's id
    public function create(?int $userId, string $action): int;

    /**
     * Each filter is optional; events are newest first, $limit per page, $page starting at 1.
     *
     * @return Event[]
     */
    public function findFiltered(?string $date, ?int $userId, ?string $action, int $page, int $limit): array;

    // Number of events matching the same filters as findFiltered()
    public function countFiltered(?string $date, ?int $userId, ?string $action): int;

    /** @return string[] distinct action names, sorted */
    public function findActions(): array;

    /**
     * Counts of the given actions per day between two dates (inclusive). Days without
     * events are left out.
     *
     * @param string[] $actions
     * @return DailyCount[]
     */
    public function countPerDay(DateTimeImmutable $from, DateTimeImmutable $to, array $actions): array;
}
