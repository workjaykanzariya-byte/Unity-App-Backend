<?php

namespace App\Services\Auth;

use App\Exceptions\InvalidOtpCodeException;
use App\Exceptions\OtpMaxAttemptsException;
use App\Exceptions\OtpNotFoundOrExpiredException;
use App\Exceptions\TooManyOtpRequestsException;
use App\Mail\SendOtpEmail;
use App\Models\OtpCode;
use App\Models\Session;
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
        // 1) Normalize identifier
        $normalizedIdentifier = trim($identifier);

        if ($channel === 'email') {
            $normalizedIdentifier = strtolower($normalizedIdentifier);
        } elseif ($channel === 'sms') {
            // remove spaces but keep + if present
            $normalizedIdentifier = preg_replace('/\s+/', '', $normalizedIdentifier) ?? $normalizedIdentifier;
        }

        // 2) Optional user lookup
        $userQuery = $channel === 'email'
            ? User::where('email', $normalizedIdentifier)
            : User::where('phone', $normalizedIdentifier);

        $user = $userQuery->first();

        // 3) Cooldown logic (per identifier + purpose)
        //    Use last OTP and check diff in seconds
        $cooldownSeconds = 60; // change to 5 in local dev if you want faster testing

        $lastOtp = OtpCode::where('identifier', $normalizedIdentifier)
            ->where('purpose', $purpose)
            ->orderByDesc('created_at')
            ->first();

        if ($lastOtp) {
            $secondsSinceLast = Carbon::parse($lastOtp->created_at)->diffInSeconds(Carbon::now());

            if ($secondsSinceLast < $cooldownSeconds) {
                throw new TooManyOtpRequestsException(
                    'Please wait before requesting another OTP.'
                );
            }
        }

        // 4) Generate OTP
        $otp = (string) random_int(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(5);

        // 5) Store OTP
        OtpCode::create([
            'user_id'    => $user?->id,
            'identifier' => $normalizedIdentifier,
            'code'       => $otp,          // TODO: hash later
            'channel'    => $channel,
            'purpose'    => $purpose,
            'expires_at' => $expiresAt,
            'attempts'   => 0,
            'used'       => false,
        ]);

        // 6) Send OTP
        if ($channel === 'email') {
            try {
                Mail::to($normalizedIdentifier)->send(new SendOtpEmail($otp));
            } catch (\Throwable $e) {
                Log::error('Failed to send OTP email', [
                    'identifier' => $normalizedIdentifier,
                    'error'      => $e->getMessage(),
                ]);
            }
        } elseif ($channel === 'sms') {
            try {
                $this->smsService->sendOtp($normalizedIdentifier, $otp);
            } catch (\Throwable $e) {
                Log::error('Failed to send OTP sms', [
                    'identifier' => $normalizedIdentifier,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        // 7) Build response
        $data = [
            'identifier'            => $normalizedIdentifier,
            'channel'               => $channel,
            'purpose'               => $purpose,
            'expires_at'            => $expiresAt->toIso8601String(),
            'resend_after_seconds'  => $cooldownSeconds,
            'existing_user'         => $user !== null,
        ];

        if (config('app.env') !== 'production') {
            $data['debug_otp'] = $otp;
        }

        return $data;
    }

    public function verifyOtp(
        string $identifier,
        string $channel,
        string $purpose,
        string $code,
        ?array $deviceInfo = null,
        ?string $ipAddress = null
    ): array {
        // Normalize identifier
        $normalizedIdentifier = trim($identifier);

        if ($channel === 'email') {
            $normalizedIdentifier = strtolower($normalizedIdentifier);
        } elseif ($channel === 'sms') {
            $normalizedIdentifier = preg_replace('/\s+/', '', $normalizedIdentifier) ?? $normalizedIdentifier;
        }

        // Fetch latest valid OTP
        $otpCode = OtpCode::where('identifier', $normalizedIdentifier)
            ->where('channel', $channel)
            ->where('purpose', $purpose)
            ->where('used', false)
            ->where('expires_at', '>=', Carbon::now())
            ->orderByDesc('created_at')
            ->first();

        if (!$otpCode) {
            throw new OtpNotFoundOrExpiredException();
        }

        $maxAttempts = 5;

        if ($otpCode->attempts >= $maxAttempts) {
            $otpCode->used = true;
            $otpCode->save();

            throw new OtpMaxAttemptsException();
        }

        if ($otpCode->code !== $code) {
            $otpCode->attempts += 1;
            $otpCode->save();

            throw new InvalidOtpCodeException();
        }

        if ($otpCode->expires_at->isPast()) {
            throw new OtpNotFoundOrExpiredException();
        }

        $otpCode->used = true;
        $otpCode->save();

        $isNewUser = false;
        $user = $otpCode->user;

        if (!$user) {
            $user = $channel === 'email'
                ? User::where('email', $normalizedIdentifier)->first()
                : User::where('phone', $normalizedIdentifier)->first();
        }

        if (!$user) {
            $isNewUser = true;

            $userData = [
                'role' => 'visitor',
                'status' => 'registered_visitor',
            ];

            if ($channel === 'email') {
                $userData['email'] = $normalizedIdentifier;
                $userData['is_email_verified'] = true;
            } else {
                $userData['phone'] = $normalizedIdentifier;
                $userData['is_phone_verified'] = true;
            }

            $user = User::create($userData);
        } else {
            if ($channel === 'email' && !$user->is_email_verified) {
                $user->is_email_verified = true;
            }

            if ($channel === 'sms' && !$user->is_phone_verified) {
                $user->is_phone_verified = true;
            }

            $user->save();
        }

        $token = $user->createToken('mobile');
        $plainTextToken = $token->plainTextToken;

        $expiresAt = Carbon::now()->addDays(30);

        Session::create([
            'user_id' => $user->id,
            'token' => $plainTextToken,
            'device_info' => $deviceInfo,
            'ip' => $ipAddress,
            'expires_at' => $expiresAt,
            'revoked' => false,
        ]);

        return [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'status' => $user->status,
            ],
            'token' => $plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toIso8601String(),
            'member_status' => $user->status,
            'is_new_user' => $isNewUser,
        ];
    }
}
