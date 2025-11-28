<?php

namespace Tests\Feature\Auth;

use App\Mail\SendOtpEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RequestOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_request_otp_with_email(): void
    {
        Mail::fake();

        $payload = [
            'identifier' => 'john@example.com',
            'channel' => 'email',
            'purpose' => 'login',
        ];

        $response = $this->postJson('/api/v1/auth/request-otp', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'identifier' => 'john@example.com',
                    'channel' => 'email',
                    'purpose' => 'login',
                    'resend_after_seconds' => 60,
                    'existing_user' => false,
                ],
                'errors' => null,
            ])
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

        Mail::assertSent(SendOtpEmail::class, function (SendOtpEmail $mail) use ($payload) {
            return $mail->hasTo($payload['identifier']);
        });
    }

    public function test_can_request_otp_with_phone(): void
    {
        Log::spy();

        $payload = [
            'identifier' => '+12345678901',
            'channel' => 'sms',
            'purpose' => 'login',
        ];

        $response = $this->postJson('/api/v1/auth/request-otp', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'identifier' => '+12345678901',
                    'channel' => 'sms',
                    'purpose' => 'login',
                    'resend_after_seconds' => 60,
                    'existing_user' => false,
                ],
                'errors' => null,
            ]);

        Log::shouldHaveReceived('info')->once();
    }

    public function test_validation_failure_with_bad_identifier(): void
    {
        $payload = [
            'identifier' => 'bad-identifier',
            'channel' => 'email',
            'purpose' => 'login',
        ];

        $response = $this->postJson('/api/v1/auth/request-otp', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'data' => null,
            ])
            ->assertJsonPath('errors.0.code', 'VALIDATION_FAILED');
    }

    public function test_enforces_cooldown_per_identifier_and_purpose(): void
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
            ->assertJsonPath('errors.0.code', 'E1006_TOO_MANY_OTP_REQUESTS');
    }

    public function test_existing_user_flag_set_when_user_found(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/request-otp', [
            'identifier' => 'existing@example.com',
            'channel' => 'email',
            'purpose' => 'login',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.existing_user', true);
    }

    public function test_login_without_existing_user_is_allowed(): void
    {
        $response = $this->postJson('/api/v1/auth/request-otp', [
            'identifier' => 'newuser@example.com',
            'channel' => 'email',
            'purpose' => 'login',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.existing_user', false);
    }
}
