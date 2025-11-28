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
        $normalizedIdentifier = $this->normalizeIdentifier($identifier, $channel);

        $user = $this->findUser($normalizedIdentifier, $channel);

        $this->enforceCooldown($normalizedIdentifier, $purpose);

        $otp = (string) random_int(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(5);

        OtpCode::create([
            'user_id' => $user?->id,
            'identifier' => $normalizedIdentifier,
            'code' => $otp,
            'channel' => $channel,
            'purpose' => $purpose,
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'used' => false,
        ]);

        $this->dispatchNotification($channel, $normalizedIdentifier, $otp);

        $response = [
            'identifier' => $normalizedIdentifier,
            'channel' => $channel,
            'purpose' => $purpose,
            'expires_at' => $expiresAt->toIso8601String(),
            'resend_after_seconds' => 60,
            'existing_user' => $user !== null,
        ];

        if (config('app.env') !== 'production') {
            $response['debug_otp'] = $otp;
        }

        return $response;
    }

    private function normalizeIdentifier(string $identifier, string $channel): string
    {
        if ($channel === 'email') {
            return strtolower(trim($identifier));
        }

        return preg_replace('/\s+/', '', trim($identifier)) ?? '';
    }

    private function findUser(string $identifier, string $channel): ?User
    {
        if ($channel === 'email') {
            return User::where('email', $identifier)->first();
        }

        return User::where('phone', $identifier)->first();
    }

    private function enforceCooldown(string $identifier, string $purpose): void
    {
        $recentOtp = OtpCode::where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->where('created_at', '>', Carbon::now()->subSeconds(60))
            ->first();

        if ($recentOtp !== null) {
            throw new TooManyOtpRequestsException();
        }
    }

    private function dispatchNotification(string $channel, string $identifier, string $otp): void
    {
        if ($channel === 'email') {
            try {
                Mail::to($identifier)->send(new SendOtpEmail($otp));
            } catch (\Throwable $exception) {
                Log::error('Failed to send OTP email', [
                    'identifier' => $identifier,
                    'error' => $exception->getMessage(),
                ]);
            }

            return;
        }

        try {
            $this->smsService->sendOtp($identifier, $otp);
        } catch (\Throwable $exception) {
            Log::error('Failed to dispatch OTP via SMS', [
                'identifier' => $identifier,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
