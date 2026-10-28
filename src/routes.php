<?php

use App\Controller\PageController;
use App\Controller\ReportController;
use App\Controller\StatisticsController;
use App\Core\Router\Router;
use App\Controller\AuthController;

// Routes require the 'view_pages' permission unless another one (or Router::PUBLIC) is given
$router->get('/login', [AuthController::class, 'loginForm'], Router::PUBLIC);
$router->post('/login', [AuthController::class, 'login'], Router::PUBLIC);
$router->get('/register', [AuthController::class, 'registerForm'], Router::PUBLIC);
$router->post('/register', [AuthController::class, 'register'], Router::PUBLIC);
$router->post('/logout', [AuthController::class, 'logout'], 'authentication');

$router->get('/page-a', [PageController::class, 'pageA']);
$router->post('/page-a', [PageController::class, 'buyCow']);
$router->get('/page-b', [PageController::class, 'pageB']);
$router->post('/page-b', [PageController::class, 'download']);


$router->get('/statistics', [StatisticsController::class, 'index'], 'view_pages_with_stats');
$router->get('/reports', [ReportController::class, 'index'], 'view_pages_with_stats');



$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $input);