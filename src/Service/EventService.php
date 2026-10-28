<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Event;
use App\Repository\EventRepositoryInterface;
use DateTimeImmutable;

class EventService {

    public function __construct(private EventRepositoryInterface $eventRepository) {}

    public function log(?int $userId, string $action): void
    {
        $this->eventRepository->create($userId, $action);
    }

    /** @return Event[] */
    public function search(?string $date, ?int $userId, ?string $action, int $page, int $limit): array
    {
        return $this->eventRepository->findFiltered($date, $userId, $action, $page, $limit);
    }

    public function count(?string $date, ?int $userId, ?string $action): int
    {
        return $this->eventRepository->countFiltered($date, $userId, $action);
    }

    public function getActions(): array
    {
        return $this->eventRepository->findActions();
    }

    /**
     * Counts of the given actions per day, oldest first. Every day in the range gets
     * an entry, so days without events show up as zeros.
     *
     * @return array{days: array<string, array<string, int>>, totals: array<string, int>}
     */
    public function getDailyCounts(DateTimeImmutable $from, DateTimeImmutable $to, array $actions): array
    {
        $days = [];
        for ($day = $from; $day <= $to; $day = $day->modify('+1 day')) {
            $days[$day->format('Y-m-d')] = array_fill_keys($actions, 0);
        }
        $totals = array_fill_keys($actions, 0);

        foreach ($this->eventRepository->countPerDay($from, $to, $actions) as $count) {
            $days[$count->getDay()->format('Y-m-d')][$count->getAction()] = $count->getCount();
            $totals[$count->getAction()] += $count->getCount();
        }

        return ['days' => $days, 'totals' => $totals];
    }
}
