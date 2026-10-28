<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Event;
use App\Service\EventService;
use App\Tests\Fake\InMemoryEventRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class EventServiceTest extends TestCase {

    private const array ACTIONS = [Event::VIEW_PAGE_A, Event::CLICK_DOWNLOAD];

    private InMemoryEventRepository $events;
    private EventService $service;

    protected function setUp(): void
    {
        $this->events = new InMemoryEventRepository();
        $this->service = new EventService($this->events);
    }

    public function testLogStoresEventForUser(): void
    {
        $this->service->log(7, Event::LOGIN);

        $event = array_values($this->events->events)[0];
        $this->assertSame(7, $event['user_id']);
        $this->assertSame(Event::LOGIN, $event['action']);
    }

    public function testLogAcceptsGuestEvents(): void
    {
        $this->service->log(null, Event::VIEW_PAGE_A);

        $this->assertNull(array_values($this->events->events)[0]['user_id']);
    }

    public function testDailyCountsHaveEveryDayInRangeOldestFirstWithZeros(): void
    {
        $result = $this->service->getDailyCounts(new DateTimeImmutable('2026-10-01'), new DateTimeImmutable('2026-10-03'), self::ACTIONS);

        $this->assertSame(['2026-10-01', '2026-10-02', '2026-10-03'], array_keys($result['days']));
        $zeros = [Event::VIEW_PAGE_A => 0, Event::CLICK_DOWNLOAD => 0];
        $this->assertSame($zeros, $result['days']['2026-10-02']);
        $this->assertSame($zeros, $result['totals']);
    }

    public function testDailyCountsCountPerDayAndActionWithTotals(): void
    {
        $this->events->add(1, Event::VIEW_PAGE_A, '2026-10-01 09:00:00');
        $this->events->add(2, Event::VIEW_PAGE_A, '2026-10-01 23:59:59');
        $this->events->add(1, Event::CLICK_DOWNLOAD, '2026-10-03 00:00:00');
        $this->events->add(1, Event::VIEW_PAGE_A, '2026-10-03 12:00:00');

        $result = $this->service->getDailyCounts(new DateTimeImmutable('2026-10-01'), new DateTimeImmutable('2026-10-03'), self::ACTIONS);

        $this->assertSame([Event::VIEW_PAGE_A => 2, Event::CLICK_DOWNLOAD => 0], $result['days']['2026-10-01']);
        $this->assertSame([Event::VIEW_PAGE_A => 1, Event::CLICK_DOWNLOAD => 1], $result['days']['2026-10-03']);
        $this->assertSame([Event::VIEW_PAGE_A => 3, Event::CLICK_DOWNLOAD => 1], $result['totals']);
    }

    public function testDailyCountsIgnoreOtherActionsAndDaysOutsideRange(): void
    {
        $this->events->add(1, Event::LOGIN, '2026-10-02 09:00:00');
        $this->events->add(1, Event::VIEW_PAGE_A, '2026-09-30 23:59:59');
        $this->events->add(1, Event::VIEW_PAGE_A, '2026-10-04 00:00:00');

        $result = $this->service->getDailyCounts(new DateTimeImmutable('2026-10-01'), new DateTimeImmutable('2026-10-03'), self::ACTIONS);

        $this->assertSame([Event::VIEW_PAGE_A => 0, Event::CLICK_DOWNLOAD => 0], $result['totals']);
    }

    public function testDailyCountsForSingleDay(): void
    {
        $this->events->add(1, Event::CLICK_DOWNLOAD, '2026-10-02 10:00:00');

        $day = new DateTimeImmutable('2026-10-02');
        $result = $this->service->getDailyCounts($day, $day, self::ACTIONS);

        $this->assertSame(['2026-10-02'], array_keys($result['days']));
        $this->assertSame(1, $result['days']['2026-10-02'][Event::CLICK_DOWNLOAD]);
    }

    public function testSearchPassesFiltersThrough(): void
    {
        $this->events->add(1, Event::LOGIN, '2026-10-01 09:00:00');
        $this->events->add(2, Event::LOGIN, '2026-10-01 10:00:00');
        $this->events->add(1, Event::LOGOUT, '2026-10-02 09:00:00');

        $events = $this->service->search('2026-10-01', 1, Event::LOGIN, 1, 500);

        $this->assertCount(1, $events);
        $this->assertSame(1, $events[0]->getUserId());
        $this->assertSame(Event::LOGIN, $events[0]->getAction());
        $this->assertEquals(new \DateTimeImmutable('2026-10-01 09:00:00'), $events[0]->getCreatedAt());
    }

    public function testSearchReturnsRequestedPageNewestFirst(): void
    {
        for ($hour = 10; $hour <= 14; $hour++) {
            $this->events->add(1, Event::LOGIN, "2026-10-01 $hour:00:00");
        }

        $page1 = $this->service->search(null, null, null, 1, 2);
        $page3 = $this->service->search(null, null, null, 3, 2);

        $this->assertSame(['14:00', '13:00'], array_map(fn(Event $e): string => $e->getCreatedAt()->format('H:i'), $page1));
        $this->assertSame(['10:00'], array_map(fn(Event $e): string => $e->getCreatedAt()->format('H:i'), $page3));
        $this->assertSame([], $this->service->search(null, null, null, 4, 2));
    }

    public function testCountUsesTheSameFiltersAsSearch(): void
    {
        $this->events->add(1, Event::LOGIN, '2026-10-01 09:00:00');
        $this->events->add(1, Event::LOGIN, '2026-10-01 10:00:00');
        $this->events->add(2, Event::LOGIN, '2026-10-01 11:00:00');
        $this->events->add(1, Event::LOGOUT, '2026-10-02 09:00:00');

        $this->assertSame(4, $this->service->count(null, null, null));
        $this->assertSame(2, $this->service->count('2026-10-01', 1, Event::LOGIN));
    }

    public function testGetActionsReturnsDistinctSortedActions(): void
    {
        $this->events->add(1, Event::LOGOUT, '2026-10-01 09:00:00');
        $this->events->add(1, Event::LOGIN, '2026-10-01 10:00:00');
        $this->events->add(2, Event::LOGIN, '2026-10-01 11:00:00');

        $this->assertSame([Event::LOGIN, Event::LOGOUT], $this->service->getActions());
    }
}
