<?php

namespace App\Exceptions;

use Exception;

class OtpMaxAttemptsException extends Exception
{
    public function __construct(
        string $message = 'Maximum OTP attempts reached. Please request a new OTP.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
