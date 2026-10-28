<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Exception\RegistrationValidationException;
use App\Repository\UserRepositoryInterface;
use App\Validator\UserValidator;

class UserService {

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserValidator $userValidator,
    ) {}

    // Returns the user when the credentials match, null otherwise
    public function authenticate(string $email, string $password): ?User
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null || !password_verify($password, $user->getPasswordHash())) {
            return null;
        }

        return $user;
    }

    /**
     * Creates a user with the 'user' role and returns their id.
     *
     * @throws RegistrationValidationException when the data is invalid or the email is taken
     */
    public function register(string $firstName, string $lastName, string $email, string $password): int
    {
        $error = $this->userValidator->validateRegistration($firstName, $lastName, $email, $password);
        if ($error !== null) {
            throw new RegistrationValidationException($error);
        }

        return $this->userRepository->create($firstName, $lastName, $email, password_hash($password, PASSWORD_DEFAULT));
    }

    public function getEmailsById(): array
    {
        return $this->userRepository->findAllEmails();
    }
}
