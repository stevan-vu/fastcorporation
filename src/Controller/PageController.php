<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Http\Response;
use App\Core\View\View;
use App\Security\Auth;
use App\Service\EventService;
use App\Entity\Event;

class PageController {

    public function __construct(
        private EventService $eventService,
        private Auth $auth,
    ) {}

    public function pageA(): Response
    {
        $this->eventService->log($this->auth->userId(), Event::VIEW_PAGE_A);

        return Response::html(View::render('pages/page_a', ['bought' => false]));
    }

    public function buyCow(): Response
    {
        $this->eventService->log($this->auth->userId(), Event::CLICK_BUY_COW);

        return Response::html(View::render('pages/page_a', ['bought' => true]));
    }

    public function pageB(): Response
    {
        $this->eventService->log($this->auth->userId(), Event::VIEW_PAGE_B);

        return Response::html(View::render('pages/page_b'));
    }


    public function download()
    {
        $this->eventService->log($this->auth->userId(), Event::CLICK_DOWNLOAD);

        Response::download('storage/dummy.exe');
    }
}
