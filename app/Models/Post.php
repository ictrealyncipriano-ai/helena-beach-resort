<?php

namespace App\Models;

use App\Support\CompressesImages;
use App\Support\HtmlSanitizer;
use App\Support\PublicCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Blog / news / promo announcement posts.
 *
 * Visible on the public site only when active and published_at has arrived
 * (or is null-safe scheduled), so admins can schedule future announcements.
 */
class Post extends Model
{
    use CompressesImages;

    protected $fillable = [
        'title', 'slug', 'excerpt', 'body', 'cover_image', 'cover_width', 'cover_height', 'is_active', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * The body is rendered with {!! !!} on the public article page, so it is
     * run through the same allow-list sanitizer used for cottage descriptions
     * before it can be persisted.
     */
    protected function body(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => app(HtmlSanitizer::class)->sanitize($value),
        );
    }

    /**
     * Posts that are currently visible on the public site.
     */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc');
    }

    /**
     * Whether this single post is visible on the public site right now.
     */
    public function isPublished(): bool
    {
        return $this->is_active
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    protected static function booted(): void
    {
        static::creating(fn (Post $post) => $post->slug = $post->slug ?: Str::slug($post->title));

        static::saving(function (Post $post) {
            if ($post->isDirty('cover_image') && $post->cover_image) {
                $dims = static::compressImage($post->cover_image);
                if ($dims) {
                    $post->cover_width = $dims['width'];
                    $post->cover_height = $dims['height'];
                }
            }
        });

        static::saved(fn () => PublicCache::flush());
        static::deleted(fn () => PublicCache::flush());
    }
}
