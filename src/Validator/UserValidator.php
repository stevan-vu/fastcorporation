<?php

declare(strict_types=1);

namespace App\Validator;

use App\Repository\UserRepositoryInterface;

class UserValidator {

    public function __construct(private UserRepositoryInterface $userRepository) {}

    // Returns the first error message, or null when the data is valid
    public function validateRegistration(string $firstName, string $lastName, string $email, string $password): ?string
    {
        return match (true) {
            $firstName === '' || $lastName === '' || $email === '' || $password === '' => 
                'All fields are required.',
            mb_strlen($firstName) > 255 || mb_strlen($lastName) > 255 || mb_strlen($email) > 255 => 
                'Fields must be at most 255 characters.',
            filter_var($email, FILTER_VALIDATE_EMAIL) === false => 'Invalid email address.',
            // we can add regex to validate special characters
            mb_strlen($password) < 8 => 'Password must be at least 8 characters.',
            $this->userRepository->findByEmail($email) !== null => 
                'An account with this email already exists.',
            default => null,
        };
    }
}
