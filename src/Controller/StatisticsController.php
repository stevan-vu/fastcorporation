<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Http\Response;
use App\Core\View\View;
use App\Service\EventService;
use App\Service\UserService;
use DateTimeImmutable;

class StatisticsController {

    private const int PER_PAGE = 50;

    public function __construct(
        private EventService $eventService,
        private UserService $userService,
    ) {}

    /** 
     * Admin only: table of events filtered by date, user and action.
     * Filters arrive as strings from the query string; "" means "all"
     */
    public function index(string $date = '', string $userId = '', string $page = '1', string $action = ''): Response
    {
        $error = null;

        $dateFilter = null;
        if ($date !== '') {
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
                $error = 'Invalid date.';
            } else {
                $dateFilter = $date;
            }
        }

        $userFilter = null;
        if ($userId !== '') {
            if (ctype_digit($userId)) {
                $userFilter = (int) $userId;
            } else {
                $error = 'Invalid user.';
            }
        }

        $actionFilter = $action !== '' ? $action : null;

        $pageNumber = 1;
        if ($page !== '' && (!ctype_digit($page) || (int) $page < 1)) {
            $error = 'Invalid page.';
        } elseif ($page !== '') {
            $pageNumber = (int) $page;
        }

        $events = [];
        $total = 0;
        $pages = 1;
        if ($error === null) {
            $total = $this->eventService->count($dateFilter, $userFilter, $actionFilter);
            $pages = max(1, (int) ceil($total / self::PER_PAGE));
            // A page past the end (e.g. after narrowing the filters) shows the last page
            $pageNumber = min($pageNumber, $pages);
            $events = $this->eventService->search($dateFilter, $userFilter, $actionFilter, $pageNumber, self::PER_PAGE);
        }

        return Response::html(View::render('pages/statistics', [
            'events' => $events,
            'total' => $total,
            'page' => $pageNumber,
            'pages' => $pages,
            'error' => $error,
            'users' => $this->userService->getEmailsById(),
            'actions' => $this->eventService->getActions(),
            'date' => $date,
            'userId' => $userId,
            'action' => $action,
        ]), $error === null ? 200 : 422);
    }
}
