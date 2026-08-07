<?php

namespace Tests\Unit;

use App\Models\Inquiry;
use App\Services\InquiryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_generates_reference_code(): void
    {
        $service = app(InquiryService::class);

        $inquiry = $service->store([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Test message',
        ]);

        $this->assertNotNull($inquiry->reference_code);
        $this->assertStringStartsWith('HB-', $inquiry->reference_code);
        $this->assertMatchesRegularExpression('/^HB-[A-F0-9]{6}$/', $inquiry->reference_code);
    }

    public function test_create_sets_source_to_website(): void
    {
        $service = app(InquiryService::class);

        $inquiry = $service->store([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Test message',
        ]);

        $this->assertEquals('website', $inquiry->source);
    }

    public function test_create_with_cottage(): void
    {
        $this->seed();

        $cottage = \App\Models\Cottage::first();
        $service = app(InquiryService::class);

        $inquiry = $service->store([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Test message',
            'cottage_id' => $cottage->id,
        ]);

        $this->assertEquals($cottage->id, $inquiry->cottage_id);
    }

    public function test_reference_codes_are_unique_across_many_creates(): void
    {
        $service = app(InquiryService::class);

        $codes = [];

        for ($i = 0; $i < 25; $i++) {
            $inquiry = $service->store([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'message' => 'Test message',
            ]);
            $codes[] = $inquiry->reference_code;
        }

        $this->assertCount(25, $codes);
        $this->assertSame(25, count(array_unique($codes)));

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^HB-[A-F0-9]{6}$/', $code);
        }
    }

    public function test_create_generates_non_enumerable_token(): void
    {
        $service = app(InquiryService::class);

        $inquiry = $service->store([
            'name' => 'Token User',
            'email' => 'token@example.com',
            'message' => 'Test message',
        ]);

        $this->assertNotNull($inquiry->token);
        $this->assertSame(40, strlen($inquiry->token));
    }
}
