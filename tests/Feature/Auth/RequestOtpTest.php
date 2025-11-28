<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_request_otp_with_valid_email_login(): void
    {
        $response = $this->postJson('/api/v1/auth/request-otp', [
            'identifier' => 'user@example.com',
            'channel' => 'email',
            'purpose' => 'login',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'data' => [
                    'identifier',
                    'channel',
                    'purpose',
                    'expires_at',
                    'resend_after_seconds',
                    'existing_user',
                    'debug_otp',
                ],
            ]);
    }

    public function test_can_request_otp_with_valid_phone_sms(): void
    {
        $response = $this->postJson('/api/v1/auth/request-otp', [
            'identifier' => '+919876543210',
            'channel' => 'sms',
            'purpose' => 'signup',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.channel', 'sms')
            ->assertJsonPath('data.identifier', '+919876543210');
    }

    public function test_fails_validation_for_invalid_identifier(): void
    {
        $response = $this->postJson('/api/v1/auth/request-otp', [
            'identifier' => 'not-a-valid',
            'channel' => 'email',
            'purpose' => 'login',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonFragment(['code' => 'VALIDATION_FAILED']);
    }

    public function test_enforces_per_identifier_cooldown(): void
    {
        $payload = [
            'identifier' => 'cooldown@example.com',
            'channel' => 'email',
            'purpose' => 'login',
        ];

        $first = $this->postJson('/api/v1/auth/request-otp', $payload);
        $first->assertStatus(200);

        $second = $this->postJson('/api/v1/auth/request-otp', $payload);
        $second->assertStatus(429)
            ->assertJsonFragment(['code' => 'E1006_TOO_MANY_OTP_REQUESTS']);
    }

    public function test_existing_user_flag_true_when_user_exists(): void
    {
        User::factory()->create([
            'email' => 'exists@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/request-otp', [
            'identifier' => 'exists@example.com',
            'channel' => 'email',
            'purpose' => 'login',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.existing_user', true);
    }

    public function test_existing_user_flag_false_when_no_user(): void
    {
        $response = $this->postJson('/api/v1/auth/request-otp', [
            'identifier' => 'new@example.com',
            'channel' => 'email',
            'purpose' => 'login',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.existing_user', false);
    }
}
