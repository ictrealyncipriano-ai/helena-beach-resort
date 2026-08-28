<?php

namespace Tests\Unit\Support;

use App\Support\HtmlSanitizer;
use Tests\TestCase;

/**
 * Phase 9.5 — direct unit coverage for the allow-list HTML sanitizer that
 * guards admin-entered rich text rendered with {!! !!} on public pages.
 *
 * Previously only exercised indirectly (NewsPagesTest, XssEscapingTest,
 * LegalPagesTest); these tests lock in the exact allow-list and URL-scheme
 * rules.
 */
class HtmlSanitizerTest extends TestCase
{
    private function sanitize(?string $input): ?string
    {
        return app(HtmlSanitizer::class)->sanitize($input);
    }

    public function test_strips_script_tag_but_keeps_text(): void
    {
        $this->assertSame(
            'alert(1)',
            $this->sanitize('<script>alert(1)</script>')
        );
    }

    public function test_strips_iframe_tag(): void
    {
        $this->assertSame(
            '',
            $this->sanitize('<iframe src="https://evil.example"></iframe>')
        );
    }

    public function test_removes_event_handler_attributes(): void
    {
        $out = $this->sanitize('<a href="/ok" onclick="steal()">link</a>');

        $this->assertStringContainsString('href="/ok"', $out);
        $this->assertStringNotContainsString('onclick', $out);
    }

    public function test_removes_disallowed_tag_but_keeps_children(): void
    {
        $this->assertSame(
            'Important text',
            $this->sanitize('<div><span>Important </span>text</div>')
        );
    }

    public function test_drops_javascript_url(): void
    {
        $out = $this->sanitize('<a href="javascript:alert(1)">bad</a>');

        $this->assertStringNotContainsString('javascript:', $out);
    }

    public function test_drops_javascript_url_with_control_char_obfuscation(): void
    {
        // DOMDocument decodes &#x09; so the input becomes a literal tab between
        // "javascript" and ":". The sanitizer removes control whitespace first.
        $out = $this->sanitize('<a href="jav&#x09;ascript:alert(1)">bad</a>');

        $this->assertStringNotContainsString('javascript', $out);
    }

    public function test_drops_data_url(): void
    {
        $out = $this->sanitize('<a href="data:text/html;base64,PHNjcmlwdD4=">bad</a>');

        $this->assertStringNotContainsString('data:', $out);
    }

    public function test_allows_http_https_mailto_tel_and_scheme_relative(): void
    {
        $this->assertStringContainsString('href="https://example.com"', $this->sanitize('<a href="https://example.com">x</a>'));
        $this->assertStringContainsString('href="mailto:a@b.com"', $this->sanitize('<a href="mailto:a@b.com">x</a>'));
        $this->assertStringContainsString('href="tel:123"', $this->sanitize('<a href="tel:123">x</a>'));
        $this->assertStringContainsString('href="//cdn.example/x.png"', $this->sanitize('<a href="//cdn.example/x.png">x</a>'));
    }

    public function test_allows_relative_path(): void
    {
        $this->assertStringContainsString('href="/cottages"', $this->sanitize('<a href="/cottages">x</a>'));
    }

    public function test_allowed_attributes_are_kept_and_others_stripped(): void
    {
        $out = $this->sanitize('<a href="/ok" title="T" class="foo">x</a>');

        $this->assertStringContainsString('href="/ok"', $out);
        $this->assertStringContainsString('title="T"', $out);
        $this->assertStringNotContainsString('class', $out);
    }

    public function test_img_allows_src_alt_title_only(): void
    {
        $out = $this->sanitize('<img src="/a.png" alt="A" width="100" onerror="x()">');

        $this->assertStringContainsString('src="/a.png"', $out);
        $this->assertStringContainsString('alt="A"', $out);
        $this->assertStringNotContainsString('width', $out);
        $this->assertStringNotContainsString('onerror', $out);
    }

    public function test_null_and_empty_are_passthrough(): void
    {
        $this->assertNull($this->sanitize(null));
        $this->assertSame('', $this->sanitize(''));
        $this->assertSame('   ', $this->sanitize('   '));
    }

    public function test_utf8_content_round_trips(): void
    {
        $this->assertStringContainsString('Joyeria', $this->sanitize('<p>Joyeria & Friends</p>'));
    }

    public function test_allowed_block_elements_survive(): void
    {
        $out = $this->sanitize('<p><strong>Bold</strong> <ul><li>item</li></ul> <blockquote>q</blockquote></p>');

        $this->assertStringContainsString('<p>', $out);
        $this->assertStringContainsString('<strong>', $out);
        $this->assertStringContainsString('<ul>', $out);
        $this->assertStringContainsString('<li>', $out);
        $this->assertStringContainsString('<blockquote>', $out);
    }
}
