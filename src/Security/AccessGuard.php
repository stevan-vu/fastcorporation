<?php

declare(strict_types=1);

namespace App\Security;

use App\Core\Http\Response;
use App\Core\Router\Router;

// Security checks that run before every controller: CSRF token, then login and permission
class AccessGuard {

    public function __construct(
        private Auth $auth,
        private Csrf $csrf,
    ) {}

    /**
     * Returns a response that stops the request, or null to let it through
    */ 
    public function check(string $method, ?string $permission, array $input): ?Response
    {
        if ($method === 'POST' && !$this->csrf->isValid($input[Csrf::FIELD] ?? null)) {
            return Response::html('Your form has expired. Go back, reload the page and try again.', 403);
        }

        if ($permission === Router::PUBLIC) {
            return null;
        }

        if (!$this->auth->check()) {
            return Response::redirect('/login');
        }

        if (!$this->auth->can($permission)) {
            return Response::html('Forbidden', 403);
        }

        return null;
    }
}
