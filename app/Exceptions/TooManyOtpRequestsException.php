<?php

namespace App\Exceptions;

use Exception;

class TooManyOtpRequestsException extends Exception
{
    public function __construct(
        string $message = 'Please wait before requesting another OTP.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
