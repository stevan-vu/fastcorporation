<?php


// Built-in dev server: let it serve existing static files (css, js, images) directly
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

require_once __DIR__ . '/../src/bootstrap.php';

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

// Data every template needs: the menu checks permissions, forms include the CSRF token
App\Core\View\View::share('auth', $injector->make(App\Security\Auth::class));
App\Core\View\View::share('csrfToken', $injector->make(App\Security\Csrf::class)->token());



$input = $_POST + $_GET;


require_once __DIR__ .'/../src/routes.php';
