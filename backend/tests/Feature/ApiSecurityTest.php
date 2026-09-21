<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
    }

    public function test_settings_endpoint_only_returns_allowlisted_keys(): void
    {
        Setting::create(['key' => 'site_name', 'value' => 'MTsN 1', 'group' => 'general']);
        Setting::create(['key' => 'secret_api_token', 'value' => 'super-secret-value', 'group' => 'general']);
        Setting::create(['key' => 'smtp_password', 'value' => 'hunter2', 'group' => 'general']);

        $response = $this->getJson('/api/v1/settings');

        $response->assertOk();
        $response->assertJsonFragment(['site_name' => 'MTsN 1']);
        $response->assertJsonMissing(['secret_api_token' => 'super-secret-value']);
        $response->assertJsonMissingPath('secret_api_token');
        $response->assertJsonMissingPath('smtp_password');
    }

    public function test_public_api_is_rate_limited(): void
    {
        // Batas 120/menit — request ke-121 harus 429.
        for ($i = 0; $i < 120; $i++) {
            $this->getJson('/api/v1/settings')->assertOk();
        }

        $this->getJson('/api/v1/settings')->assertStatus(429);
    }

    public function test_contact_honeypot_silently_rejects_bot_submissions(): void
    {
        $payload = [
            'name' => 'Bot Spammer',
            'email' => 'bot@spam.test',
            'message' => 'Buy cheap stuff at example.com now!!!',
            'website' => 'http://spam-link.test',
        ];

        $this->postJson('/api/v1/contacts', $payload)
            ->assertCreated()
            ->assertJsonMissingPath('id');

        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_contact_accepts_legitimate_submission_without_honeypot(): void
    {
        $payload = [
            'name' => 'Wali Murid',
            'email' => 'wali@example.com',
            'message' => 'Saya ingin bertanya tentang jadwal PPDB tahun ini.',
        ];

        $this->postJson('/api/v1/contacts', $payload)
            ->assertCreated()
            ->assertJsonPath('id', fn ($id) => is_int($id));

        $this->assertDatabaseHas('contacts', ['email' => 'wali@example.com']);
    }

    public function test_contact_rejects_too_short_message(): void
    {
        $this->postJson('/api/v1/contacts', [
            'name' => 'X',
            'email' => 'x@example.com',
            'message' => 'hi',
        ])->assertStatus(422);

        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_responses_carry_baseline_security_headers(): void
    {
        $response = $this->getJson('/api/v1/settings');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_contact_still_honours_strict_throttle(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/v1/contacts', [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'message' => 'Pesan uji coba yang cukup panjang untuk lolos validasi.',
            ])->assertCreated();
        }

        $this->postJson('/api/v1/contacts', [
            'name' => 'User 7',
            'email' => 'user7@example.com',
            'message' => 'Pesan uji coba yang cukup panjang untuk lolos validasi.',
        ])->assertStatus(429);

        Contact::query()->delete();
    }
}
