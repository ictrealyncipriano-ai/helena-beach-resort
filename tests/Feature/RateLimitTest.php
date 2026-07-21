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
}
