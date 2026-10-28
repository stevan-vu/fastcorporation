<?php

declare(strict_types= 1);

namespace App\Core\Router;

use App\Core\Http\Response;
use App\Security\AccessGuard;
use App\Security\Csrf;
use Auryn\Injector;

class Router {
    // Not protected routes
    public const ?string PUBLIC = null;

    private const string DEFAULT_PERMISSION = 'view_pages';

    private $routes = ['GET' => [], 'POST' => []];

    public function __construct(
        private Injector $injector,
        private AccessGuard $guard,
    ) {}

    public function get(string $route, array $controller, ?string $permission = self::DEFAULT_PERMISSION): void
    {
        $this->routes['GET'][$route] = [
            'handler' => $controller, 
            'permission' => $permission,
        ];
    }

    public function post(string $route, array $controller, ?string $permission = self::DEFAULT_PERMISSION): void
    {
        $this->routes['POST'][$route] = [
            'handler' => $controller, 
            'permission' => $permission,
        ];
    }

    /**
 * Dispatches the request to the matched controller action.
 *
 * Applies CSRF protection and authorization before
 * invoking the controller.
 *
 * @param array<string, mixed> $input
 */
    public function dispatch(string $method, string $uri, array $input): void
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $route = $this->routes[$method][$path] ?? null;

        if ($route === null) {
            Response::html('Not Found', 404)->send();
            return;
        }

        $denied = $this->guard->check($method, $route['permission'], $input);
        if ($denied !== null) {
            $denied->send();
            return;
        }
        // Unset because it is already checked andcontrollers don't need it
        unset($input[Csrf::FIELD]);

        [$controller, $action] = $route['handler'];

        $args = [];
        foreach ($input as $name => $value) {
            $args[":$name"] = $value;
        }

        $this->injector->execute([$controller, $action], $args)?->send();
    }
}
