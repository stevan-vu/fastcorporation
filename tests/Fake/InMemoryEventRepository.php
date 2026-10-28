<?php

declare(strict_types=1);

namespace App\Tests\Fake;

use App\Entity\DailyCount;
use App\Entity\Event;
use App\Repository\EventRepositoryInterface;
use DateTimeImmutable;

// Stand-in for the PDO repository; returns the same types as the real one (Event and DailyCount objects)
class InMemoryEventRepository implements EventRepositoryInterface {

    /** @var array<int, array{id: int, user_id: ?int, action: string, created_at: string}> */
    public array $events = [];

    private int $nextId = 1;

    public function create(?int $userId, string $action): int
    {
        return $this->add($userId, $action, date('Y-m-d H:i:s'));
    }

    // Test helper: store an event with a specific timestamp
    public function add(?int $userId, string $action, string $createdAt): int
    {
        $id = $this->nextId++;
        $this->events[$id] = ['id' => $id, 'user_id' => $userId, 'action' => $action, 'created_at' => $createdAt];

        return $id;
    }

    public function findFiltered(?string $date, ?int $userId, ?string $action, int $page, int $limit): array
    {
        $rows = $this->filter($date, $userId, $action);
        usort($rows, fn(array $a, array $b): int => [$b['created_at'], $b['id']] <=> [$a['created_at'], $a['id']]);

        return array_map(function (array $e): Event {
            $event = new Event();
            $event->setId($e['id']);
            $event->setUserId($e['user_id']);
            $event->setAction($e['action']);
            $event->setCreatedAt(new DateTimeImmutable($e['created_at']));

            return $event;
        }, array_slice($rows, ($page - 1) * $limit, $limit));
    }

    public function countFiltered(?string $date, ?int $userId, ?string $action): int
    {
        return \count($this->filter($date, $userId, $action));
    }

    private function filter(?string $date, ?int $userId, ?string $action): array
    {
        return array_filter($this->events, fn(array $e): bool =>
            ($date === null || str_starts_with($e['created_at'], $date))
            && ($userId === null || $e['user_id'] === $userId)
            && ($action === null || $e['action'] === $action)
        );
    }

    public function findActions(): array
    {
        $actions = array_values(array_unique(array_column($this->events, 'action')));
        sort($actions);

        return $actions;
    }

    public function countPerDay(DateTimeImmutable $from, DateTimeImmutable $to, array $actions): array
    {
        $fromDay = $from->format('Y-m-d');
        $toDay = $to->format('Y-m-d');

        $totals = [];
        foreach ($this->events as $e) {
            $day = substr($e['created_at'], 0, 10);
            if ($day >= $fromDay && $day <= $toDay && in_array($e['action'], $actions, true)) {
                $totals[$day][$e['action']] = ($totals[$day][$e['action']] ?? 0) + 1;
            }
        }

        $counts = [];
        foreach ($totals as $day => $byAction) {
            foreach ($byAction as $action => $total) {
                $count = new DailyCount();
                $count->setDay(new DateTimeImmutable($day));
                $count->setAction($action);
                $count->setCount($total);
                $counts[] = $count;
            }
        }

        return $counts;
    }
}
