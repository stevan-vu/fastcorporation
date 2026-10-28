<?php
declare(strict_types= 1);

namespace App\Repository;

use App\Entity\DailyCount;
use App\Entity\Event;
use DateTimeImmutable;
use PDO;

class EventRepository implements EventRepositoryInterface {
    public function __construct(private PDO $pdo)
    {}

    public function create(?int $userId, string $action): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO events (user_id, action) VALUES (:user_id, :action)");
        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findFiltered(?string $date, ?int $userId, ?string $action, int $page, int $limit): array
    {
        [$where, $params] = $this->buildFilter($date, $userId, $action);

        $offset = ($page -1) * $limit;

        $sql = 'SELECT e.id, e.created_at, e.action, e.user_id, u.email
                FROM events e
                LEFT JOIN users u ON u.id = e.user_id'
            . $where
            . \sprintf(' ORDER BY e.created_at DESC, e.id DESC LIMIT %d OFFSET %d',  $limit, $offset);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $events = [];
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $event = new Event();
            $event->setId((int) $row->id);
            $event->setUserId($row->user_id !== null ? (int) $row->user_id : null);
            $event->setAction($row->action);
            $event->setCreatedAt(new DateTimeImmutable($row->created_at));
            $event->setUserEmail($row->email);
            $events[] = $event;
        }

        return $events;
    }

    public function countFiltered(?string $date, ?int $userId, ?string $action): int
    {
        [$where, $params] = $this->buildFilter($date, $userId, $action);

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM events e' . $where);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function findActions(): array
    {
        return $this->pdo->query('SELECT DISTINCT action FROM events ORDER BY action')
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    public function countPerDay(DateTimeImmutable $from, DateTimeImmutable $to, array $actions): array
    {
        if ($actions === []) {
            return [];
        }

        $placeholders = [];
        $params = [
            'from_start' => $from->format('Y-m-d 00:00:00'),
            'to_end' => $to->modify('+1 day')->format('Y-m-d 00:00:00'),
        ];
        foreach (array_values($actions) as $i => $action) {
            $placeholders[] = ':action' . $i;
            $params["action$i"] = $action;
        }

        $stmt = $this->pdo->prepare(
            'SELECT DATE(created_at) AS day, action, COUNT(*) AS total
             FROM events
             WHERE created_at >= :from_start AND created_at < :to_end
               AND action IN (' . implode(', ', $placeholders) . ')
             GROUP BY day, action'
        );
        $stmt->execute($params);

        $counts = [];
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $count = new DailyCount();
            $count->setDay(new DateTimeImmutable($row->day));
            $count->setAction($row->action);
            $count->setCount((int) $row->total);
            $counts[] = $count;
        }

        return $counts;
    }

    /**
     * WHERE clause and bound params shared by findFiltered() and countFiltered()
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildFilter(?string $date, ?int $userId, ?string $action): array
    {
        $where = [];
        $params = [];

        if ($date !== null) {
            // A range instead of DATE(created_at) so the created_at index can be used
            $where[] = 'e.created_at >= :day_start AND e.created_at < :day_end';
            $params['day_start'] = $date . ' 00:00:00';
            $params['day_end'] = date('Y-m-d', strtotime($date . ' +1 day')) . ' 00:00:00';
        }
        if ($userId !== null) {
            $where[] = 'e.user_id = :user_id';
            $params['user_id'] = $userId;
        }
        if ($action !== null) {
            $where[] = 'e.action = :action';
            $params['action'] = $action;
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
    }
}
