<?php

namespace App\Exceptions;

use Exception;

class InvalidOtpCodeException extends Exception
{
    public function __construct(
        string $message = 'Invalid OTP code.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
