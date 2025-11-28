<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

class SmsService
{
    public function sendOtp(string $phone, string $otp): void
    {
        Log::info("Sending OTP {$otp} to {$phone}");
    }
}
