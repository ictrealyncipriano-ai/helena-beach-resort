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
        $this->assertStringEndsWith('000001', $inquiry->reference_code);
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

    public function test_reference_code_increments(): void
    {
        $service = app(InquiryService::class);

        $first = $service->store([
            'name' => 'First',
            'email' => 'first@example.com',
            'message' => 'First',
        ]);

        $second = $service->store([
            'name' => 'Second',
            'email' => 'second@example.com',
            'message' => 'Second',
        ]);

        $this->assertEquals('HB-000001', $first->reference_code);
        $this->assertEquals('HB-000002', $second->reference_code);
    }
}
