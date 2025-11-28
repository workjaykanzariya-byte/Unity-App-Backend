<?php

namespace Tests\Feature\Auth;

use App\Models\OtpCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_verify_valid_otp_and_get_token(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'is_email_verified' => false,
        ]);

        OtpCode::create([
            'user_id' => $user->id,
            'identifier' => 'john@example.com',
            'code' => '123456',
            'channel' => 'email',
            'purpose' => 'login',
            'expires_at' => Carbon::now()->addMinutes(5),
            'attempts' => 0,
            'used' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'identifier' => 'john@example.com',
            'channel' => 'email',
            'purpose' => 'login',
            'code' => '123456',
            'device_info' => [
                'os' => 'Android',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'email', 'phone', 'role', 'status'],
                    'token',
                    'token_type',
                    'expires_at',
                    'member_status',
                    'is_new_user',
                ],
            ])
            ->assertJsonPath('data.is_new_user', false);

        $this->assertDatabaseHas('sessions', [
            'user_id' => $user->id,
        ]);
    }

    public function test_cannot_verify_with_invalid_code(): void
    {
        $user = User::factory()->create([
            'phone' => '+1234567890',
            'is_phone_verified' => false,
        ]);

        OtpCode::create([
            'user_id' => $user->id,
            'identifier' => '+1234567890',
            'code' => '654321',
            'channel' => 'sms',
            'purpose' => 'login',
            'expires_at' => Carbon::now()->addMinutes(5),
            'attempts' => 0,
            'used' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'identifier' => '+1234567890',
            'channel' => 'sms',
            'purpose' => 'login',
            'code' => '000000',
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment(['code' => 'E2003_INVALID_OTP']);

        $this->assertDatabaseHas('otp_codes', [
            'identifier' => '+1234567890',
            'attempts' => 1,
        ]);
    }

    public function test_cannot_use_expired_otp(): void
    {
        OtpCode::create([
            'identifier' => 'expired@example.com',
            'code' => '111111',
            'channel' => 'email',
            'purpose' => 'login',
            'expires_at' => Carbon::now()->subMinutes(1),
            'attempts' => 0,
            'used' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'identifier' => 'expired@example.com',
            'channel' => 'email',
            'purpose' => 'login',
            'code' => '111111',
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment(['code' => 'E2001_OTP_NOT_FOUND_OR_EXPIRED']);
    }

    public function test_new_user_created_when_identifier_not_existing(): void
    {
        OtpCode::create([
            'identifier' => 'newuser@example.com',
            'code' => '222222',
            'channel' => 'email',
            'purpose' => 'login',
            'expires_at' => Carbon::now()->addMinutes(5),
            'attempts' => 0,
            'used' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'identifier' => 'newuser@example.com',
            'channel' => 'email',
            'purpose' => 'login',
            'code' => '222222',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_new_user', true)
            ->assertJsonPath('data.user.email', 'newuser@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'is_email_verified' => true,
        ]);
    }

    public function test_max_attempts_lockout(): void
    {
        OtpCode::create([
            'identifier' => '+19998887777',
            'code' => '333333',
            'channel' => 'sms',
            'purpose' => 'login',
            'expires_at' => Carbon::now()->addMinutes(5),
            'attempts' => 5,
            'used' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'identifier' => '+19998887777',
            'channel' => 'sms',
            'purpose' => 'login',
            'code' => '333333',
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment(['code' => 'E2002_OTP_MAX_ATTEMPTS_REACHED']);

        $this->assertDatabaseHas('otp_codes', [
            'identifier' => '+19998887777',
            'used' => true,
        ]);
    }
}
