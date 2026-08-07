<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_contact_form_rate_limited_after_3_requests(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->post('/contact', [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'message' => 'Test message',
            ])->assertStatus(302);
        }

        $this->post('/contact', [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'message' => 'Should be blocked',
        ])->assertStatus(429);
    }

    public function test_admin_login_rate_limited_after_5_requests(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', [
                'email' => "admin{$i}@example.com",
                'password' => 'wrong-password',
            ])->assertStatus(302);
        }

        $this->post('/admin/login', [
            'email' => 'blocked-admin@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_booking_lookup_rate_limited_after_5_requests(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/booking/lookup', [
                'email' => "lookup{$i}@example.com",
                'reference_code' => 'HB-NOPE',
            ])->assertStatus(302);
        }

        $this->post('/booking/lookup', [
            'email' => 'blocked@example.com',
            'reference_code' => 'HB-BLOCKED',
        ])->assertStatus(429);
    }
}
