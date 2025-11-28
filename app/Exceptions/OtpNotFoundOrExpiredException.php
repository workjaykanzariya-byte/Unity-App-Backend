<?php

namespace App\Exceptions;

use Exception;

class OtpNotFoundOrExpiredException extends Exception
{
    public function __construct(
        string $message = 'OTP not found or expired.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
