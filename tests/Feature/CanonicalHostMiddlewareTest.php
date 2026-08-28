<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 9.4 — the CanonicalHost middleware is production-gated: it must 301
 * redirect non-canonical hosts and http→https only when APP_ENV=production,
 * and pass straight through in every other environment (so localhost dev is
 * never disturbed).
 *
 * Host and scheme are set by the absolute URL passed to get(), which is the
 * deterministic way to exercise them in a feature test.
 */
class CanonicalHostMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function tearDown(): void
    {
        // Restore a non-production env so we never leak production behaviour
        // into other tests in the same process.
        $this->app->detectEnvironment(fn () => 'testing');
        config(['app.url' => config('app.url')]);
        parent::tearDown();
    }

    private function inProduction(string $url): void
    {
        // detectEnvironment reassigns the Application's captured env, which is
        // what app()->environment('production') actually reads.
        $this->app->detectEnvironment(fn () => 'production');
        config(['app.url' => $url]);
    }

    public function test_non_canonical_host_redirects_301_in_production(): void
    {
        $this->inProduction('https://helena.example.com');

        $response = $this->get('http://helena.example.com/cottages');

        // The 301 must point at the canonical host/scheme.
        $response->assertStatus(301);
        $this->assertStringStartsWith('https://helena.example.com/cottages', $response->headers->get('Location'));
    }

    public function test_http_redirects_to_https_in_production(): void
    {
        $this->inProduction('https://helena.example.com');

        // Same host, but a plain http request (canonical is https).
        $response = $this->get('http://helena.example.com/');

        $response->assertStatus(301);
        $this->assertStringStartsWith('https://helena.example.com/', $response->headers->get('Location'));
    }

    public function test_canonical_host_and_scheme_passes_through(): void
    {
        // canonical url is http://helena.example.com and the request matches
        // host + scheme, so no redirect occurs.
        $this->inProduction('http://helena.example.com');

        $this->get('http://helena.example.com/')
            ->assertStatus(200);
    }

    public function test_no_redirect_outside_production(): void
    {
        // Env forced back to testing; a non-canonical host must NOT redirect.
        $this->app->detectEnvironment(fn () => 'testing');
        config(['app.url' => 'https://helena.example.com']);

        $this->get('http://www.helena.example.com/')
            ->assertStatus(200);
    }
}
