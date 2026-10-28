<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Http\Response;
use App\Core\View\View;
use App\Entity\Event;
use App\Exception\RegistrationValidationException;
use App\Security\Auth;
use App\Service\EventService;
use App\Service\UserService;


class AuthController {

    public function __construct(
        private UserService $userService,
        private EventService $eventService,
        private Auth $auth,
    ) {}

    public function loginForm(): Response
    {
        return Response::html(View::render('pages/login_form'));
    }

    public function login(string $email = '', string $password = ''): Response
    {
        $user = $this->userService->authenticate($email, $password);

        if ($user === null) {
            return Response::html(View::render('pages/login_form', [
                'error' => 'Invalid email or password.',
                'email' => $email,
            ]), 401);
        }

        $this->auth->login($user->getId());
        $this->eventService->log($user->getId(), Event::LOGIN);

        return Response::redirect('/page-a');
    }

    public function logout(): Response
    {
        $this->eventService->log($this->auth->userId(), Event::LOGOUT);
        $this->auth->logout();

        return Response::redirect('/login');
    }

    public function registerForm(): Response
    {
        return Response::html(View::render('pages/registration'));
    }

    public function register(string $firstName = '', string $lastName = '', string $email = '', string $password = ''): Response
    {
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        $email = trim($email);

        try {
            $userId = $this->userService->register($firstName, $lastName, $email, $password);
        } catch (RegistrationValidationException $e) {
            return Response::html(View::render('pages/registration', [
                'error' => $e->getMessage(),
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $email,
            ]), 422);
        }

        $this->eventService->log($userId, Event::REGISTRATION);

        $this->auth->login($userId);

        return Response::redirect('/page-a');
    }
}
