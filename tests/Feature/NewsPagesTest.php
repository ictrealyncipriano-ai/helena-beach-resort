<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function publishedPost(array $overrides = []): Post
    {
        return Post::create(array_merge([
            'title' => 'Welcome to Helena',
            'body' => '<p>Hello guests</p>',
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    public function test_news_index_returns_200(): void
    {
        $this->get(route('news.index'))->assertStatus(200);
    }

    public function test_news_index_lists_published_posts_only(): void
    {
        $this->publishedPost();
        $this->publishedPost(['title' => 'Hidden Inactive', 'is_active' => false]);
        $this->publishedPost(['title' => 'Scheduled Future', 'published_at' => now()->addWeek()]);

        $this->get(route('news.index'))
            ->assertSee('Welcome to Helena')
            ->assertDontSee('Hidden Inactive')
            ->assertDontSee('Scheduled Future');
    }

    public function test_news_show_returns_200_for_published_post(): void
    {
        $post = $this->publishedPost();

        $this->get(route('news.show', $post))
            ->assertStatus(200)
            ->assertSee('Welcome to Helena')
            ->assertSee('Hello guests', false)
            ->assertSee('NewsArticle', false);
    }

    public function test_news_show_returns_404_for_inactive_post(): void
    {
        $post = $this->publishedPost(['is_active' => false]);

        $this->get(route('news.show', $post))->assertStatus(404);
    }

    public function test_news_show_returns_404_for_scheduled_post(): void
    {
        $post = $this->publishedPost(['published_at' => now()->addWeek()]);

        $this->get(route('news.show', $post))->assertStatus(404);
    }

    public function test_post_body_is_sanitized_before_render(): void
    {
        $post = $this->publishedPost([
            'body' => '<script>alert("xss")</script><p>Safe content</p>',
        ]);

        $this->get(route('news.show', $post))
            ->assertSee('Safe content', false);

        $this->assertStringNotContainsString('<script', $post->fresh()->body);
        $this->assertStringNotContainsString('<iframe', $post->fresh()->body);
        $this->assertStringContainsString('<p>Safe content</p>', $post->fresh()->body);
    }

    public function test_sitemap_includes_news_and_posts(): void
    {
        $post = $this->publishedPost();

        $this->get('/sitemap.xml')
            ->assertSee('/news', false)
            ->assertSee($post->slug, false);
    }

    public function test_home_page_shows_latest_news(): void
    {
        $post = $this->publishedPost();

        $this->get(route('home'))
            ->assertSee('News & Updates')
            ->assertSee('Welcome to Helena');
    }
}
