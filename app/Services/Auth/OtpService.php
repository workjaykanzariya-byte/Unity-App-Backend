<?php

namespace App\Services\Auth;

use App\Exceptions\TooManyOtpRequestsException;
use App\Mail\SendOtpEmail;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\Sms\SmsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public function __construct(private readonly SmsService $smsService)
    {
    }

    public function requestOtp(string $identifier, string $channel, string $purpose): array
    {
        $identifier = trim($identifier);
        if ($channel === 'email') {
            $identifier = strtolower($identifier);
        } elseif ($channel === 'sms') {
            $identifier = preg_replace('/\s+/', '', $identifier) ?? $identifier;
        }

        $userQuery = $channel === 'email'
            ? User::where('email', $identifier)
            : User::where('phone', $identifier);

        $user = $userQuery->first();

        $recentOtpExists = OtpCode::where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->where('created_at', '>', Carbon::now()->subSeconds(60))
            ->exists();

        if ($recentOtpExists) {
            throw new TooManyOtpRequestsException();
        }

        $otp = (string) random_int(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(5);

        OtpCode::create([
            'user_id' => $user?->id,
            'identifier' => $identifier,
            'code' => $otp,
            'channel' => $channel,
            'purpose' => $purpose,
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'used' => false,
        ]);

        if ($channel === 'email') {
            try {
                Mail::to($identifier)->send(new SendOtpEmail($otp));
            } catch (\Throwable $e) {
                Log::error('Failed to send OTP email', [
                    'identifier' => $identifier,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($channel === 'sms') {
            try {
                $this->smsService->sendOtp($identifier, $otp);
            } catch (\Throwable $e) {
                Log::error('Failed to send OTP sms', [
                    'identifier' => $identifier,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $data = [
            'identifier' => $identifier,
            'channel' => $channel,
            'purpose' => $purpose,
            'expires_at' => $expiresAt->toIso8601String(),
            'resend_after_seconds' => 60,
            'existing_user' => $user !== null,
        ];

        if (config('app.env') !== 'production') {
            $data['debug_otp'] = $otp;
        }

        return $data;
    }
}
