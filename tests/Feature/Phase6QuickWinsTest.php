<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase6QuickWinsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_sitemap_all_static_entries_have_lastmod(): void
    {
        $response = $this->get('/sitemap.xml');
        $content = $response->getContent();

        // Count <url> entries
        preg_match_all('/<url>/', $content, $matches);
        $urlCount = count($matches[0]);
        $this->assertGreaterThan(5, $urlCount);

        // Every <url> must contain a <lastmod> tag
        preg_match_all('/<lastmod>/', $content, $lastmodMatches);
        $this->assertEquals($urlCount, count($lastmodMatches[0]), 'Every sitemap <url> should have a <lastmod>');
    }

    public function test_booking_lookup_has_noindex(): void
    {
        $this->get('/booking/lookup')
            ->assertStatus(200)
            ->assertSee('noindex, nofollow', false);
    }

    public function test_booking_detail_has_noindex(): void
    {
        $inquiry = \App\Models\Inquiry::where('status', 'confirmed')->first();
        if (! $inquiry) {
            $this->markTestSkipped('No confirmed inquiry seeded.');
        }

        $this->withSession(['booking_access_tokens' => [$inquiry->id => $inquiry->token]])
            ->get("/booking/{$inquiry->id}")
            ->assertStatus(200)
            ->assertSee('noindex, nofollow', false);
    }

    public function test_confirmation_page_has_noindex(): void
    {
        $inquiry = \App\Models\Inquiry::first();
        if (! $inquiry) {
            $this->markTestSkipped('No inquiry seeded.');
        }

        $this->get("/booking/confirmation/{$inquiry->id}")
            ->assertStatus(200)
            ->assertSee('noindex, nofollow', false);
    }

    public function test_home_page_does_not_have_noindex(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertDontSee('noindex', false);
    }

    public function test_paginate_works_via_base_controller(): void
    {
        // Gallery uses the inherited paginate() from the base Controller
        $response = $this->get('/gallery');
        $response->assertStatus(200);
    }

    public function test_news_paginate_works_via_base_controller(): void
    {
        $response = $this->get('/news');
        $response->assertStatus(200);
    }
}
