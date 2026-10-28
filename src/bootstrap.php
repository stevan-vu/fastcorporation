<?php

use App\Core\Router\Router;
require_once __DIR__ . '/../vendor/autoload.php';

$host     = getenv('DB_HOST') ?: 'mysql';
$port     = getenv('DB_PORT') ?: '3306';
$dbname   = getenv('MYSQL_DATABASE');
$username = getenv('MYSQL_USER');
$password = getenv('MYSQL_PASSWORD');

// Use Auryn for automatic dependency resolution instead of a custom DI container.
$injector = new Auryn\Injector;
// Classes that ask for an Injector (the Router) get this configured instance, not a new empty one
$injector->share($injector);
$injector->share('PDO');
$injector->define('PDO', [
    ':dsn' => "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
    ':username' => $username,
    ':password' => $password,
]);

$pdo = $injector->make('PDO');

// Code depends on repository interfaces; these pick the implementation Auryn injects
$injector->alias(App\Repository\UserRepositoryInterface::class, App\Repository\UserRepository::class);
$injector->alias(App\Repository\EventRepositoryInterface::class, App\Repository\EventRepository::class);

// One Auth per request so the router and controllers see the same user and cached permissions
$injector->share(App\Security\Auth::class);
$injector->share(App\Security\Csrf::class);
$router = $injector->make(App\Core\Router\Router::class);
