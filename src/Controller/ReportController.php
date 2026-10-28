<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Http\Response;
use App\Core\View\View;
use App\Entity\Event;
use App\Service\EventService;
use DateTimeImmutable;

class ReportController {

    private const int DEFAULT_DAYS = 30;
    private const int MAX_DAYS = 366;

    // Report columns / chart series, in display order (the order also fixes each series' color)
    private const array METRICS = [
        Event::VIEW_PAGE_A => 'Page A views',
        Event::VIEW_PAGE_B => 'Page B views',
        Event::CLICK_BUY_COW => '"Buy a cow" clicks',
        Event::CLICK_DOWNLOAD => '"Download" clicks',
    ];

    public function __construct(private EventService $eventService) {}

    /** Admin only: page views and button clicks per day. 
     * Dates arrive as strings from the query string; "" means "use the default range"
    */
    public function index(string $from = '', string $to = ''): Response
    {
        $today = new DateTimeImmutable('today');
        $toDate = $to === '' ? $today : $this->parseDate($to);
        $fromDate = $from === '' 
            ? $toDate?->modify(\sprintf('-%d days', self::DEFAULT_DAYS - 1)) 
            : $this->parseDate($from);

        $error = match (true) {
            $fromDate === null || $toDate === null => 'Invalid date.',
            $fromDate > $toDate => '"From" must be before "To".',
            $fromDate->diff($toDate)->days >= self::MAX_DAYS => 'The range can be at most ' . self::MAX_DAYS . ' days.',
            default => null,
        };

        $counts = $error === null
            ? $this->eventService->getDailyCounts($fromDate, $toDate, array_keys(self::METRICS))
            : ['days' => [], 'totals' => array_fill_keys(array_keys(self::METRICS), 0)];

        return Response::html(View::render('pages/report', [
            'error' => $error,
            'metrics' => self::METRICS,
            'days' => $counts['days'],
            'totals' => $counts['totals'],
            'from' => $fromDate?->format('Y-m-d') ?? $from,
            'to' => $toDate?->format('Y-m-d') ?? $to,
        ]), $error === null ? 200 : 422);
    }

    private function parseDate(string $date): ?DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date ? $parsed : null;
    }
}
