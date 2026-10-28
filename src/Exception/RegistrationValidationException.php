<?php

declare(strict_types=1);

namespace App\Exception;

// Thrown by services when user input is invalid; the message is safe to show to the user
class RegistrationValidationException extends \RuntimeException {}
