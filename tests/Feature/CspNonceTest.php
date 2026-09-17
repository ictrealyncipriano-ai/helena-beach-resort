<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CSP hardening slice — the SecurityHeaders middleware must emit a strict
 * per-request nonce policy (never 'unsafe-inline' and never 'unsafe-eval':
 * Alpine runs via the @alpinejs/csp build whose AST evaluator needs no
 * runtime code generation), every executable inline script must carry that
 * nonce, no inline event-handler attributes may remain (they ignore nonces),
 * and the Organization JSON-LD must render valid structured data.
 */
class CspNonceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function scriptSrc(string $csp): string
    {
        preg_match('/script-src ([^;]+)/', $csp, $m);

        return $m[1] ?? '';
    }

    private function headerNonce(string $csp): ?string
    {
        preg_match("/'nonce-([A-Za-z0-9+\\/=]+)'/", $csp, $m);

        return $m[1] ?? null;
    }

    public function test_homepage_csp_uses_nonce_and_never_unsafe_inline(): void
    {
        $csp = $this->get('/')->headers->get('Content-Security-Policy', '');
        $scriptSrc = $this->scriptSrc($csp);

        $this->assertStringContainsString("'nonce-", $scriptSrc);
        $this->assertStringNotContainsString('unsafe-inline', $scriptSrc);
        $this->assertStringContainsString('https://www.googletagmanager.com', $scriptSrc);
    }

    public function test_script_src_never_allows_unsafe_eval(): void
    {
        // Alpine's CSP build evaluates x-* expressions without new Function,
        // so the strict policy must hold on every public entry point. If this
        // fails, all Alpine interactivity (booking picker, navbar, drawers)
        // is dead in the browser while tests stay green.
        foreach (['/', '/book', '/contact'] as $uri) {
            $csp = $this->get($uri)->headers->get('Content-Security-Policy', '');
            $this->assertStringNotContainsString(
                'unsafe-eval',
                $this->scriptSrc($csp),
                "unsafe-eval present on {$uri}"
            );
        }
    }

    public function test_no_inline_event_handler_attributes(): void
    {
        // Nonces do not whitelist on* attributes (only 'unsafe-hashes'
        // would); any remaining handler is dead markup. Alpine's @click /
        // x-on directives are plain attributes and must not match.
        foreach (['/', '/book'] as $uri) {
            $html = $this->get($uri)->getContent();
            $this->assertDoesNotMatchRegularExpression(
                '/\son(load|click|change|submit|error|mouseover|keydown|keyup)="[^"]*"/i',
                $html,
                "Inline event handler found on {$uri}"
            );
        }
    }

    public function test_consecutive_requests_receive_different_nonces(): void
    {
        $first = $this->headerNonce($this->get('/')->headers->get('Content-Security-Policy', ''));
        $second = $this->headerNonce($this->get('/')->headers->get('Content-Security-Policy', ''));

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertNotSame($first, $second);
    }

    public function test_every_executable_inline_script_carries_the_header_nonce(): void
    {
        $response = $this->get('/');
        $csp = $response->headers->get('Content-Security-Policy', '');
        $nonce = $this->headerNonce($csp);
        $html = $response->getContent();

        $this->assertNotNull($nonce);

        // Bare executable scripts (no nonce, no src, no non-JS type) are forbidden.
        preg_match_all('/<script(?![^>]*nonce=)(?![^>]*src=)(?![^>]*type=)[^>]*>/', $html, $bare);
        $this->assertSame([], $bare[0]);

        // Every nonce present in the HTML must equal the header nonce.
        preg_match_all('/<script[^>]*nonce="([^"]+)"[^>]*>/', $html, $used);
        $this->assertNotEmpty($used[1]);
        foreach ($used[1] as $usedNonce) {
            $this->assertSame($nonce, $usedNonce);
        }
    }

    public function test_organization_json_ld_parses_with_valid_context(): void
    {
        $html = $this->get('/')->getContent();

        preg_match_all(
            '/<script type="application\\/ld\\+json">(.*?)<\\/script>/s',
            $html,
            $blocks
        );
        $this->assertNotEmpty($blocks[1]);

        $found = false;
        foreach ($blocks[1] as $block) {
            $data = json_decode(trim($block), true);
            if (is_array($data) && ($data['@type'] ?? null) === 'Organization') {
                $found = true;
                $this->assertSame('https://schema.org', $data['@context']);
                $this->assertNotEmpty($data['name']);
                $this->assertNotEmpty($data['url']);
            }
        }
        $this->assertTrue($found, 'No Organization JSON-LD block found');
    }

    public function test_no_third_party_script_tags_or_cdn_dependencies_remain(): void
    {
        $response = $this->get('/');
        $html = $response->getContent();
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);

        // Same-origin Vite bundles remain allowed under script-src 'self';
        // only third-party script hosts are forbidden.
        preg_match_all('/<script[^>]*src="([^"]+)"[^>]*>/', $html, $srcs);
        foreach ($srcs[1] as $src) {
            $host = parse_url($src, PHP_URL_HOST);
            $this->assertTrue(
                $host === null || $host === $appHost,
                "Third-party script source: {$src}"
            );
        }
        $this->assertStringNotContainsString('cdn.jsdelivr.net', $html);
    }
}
