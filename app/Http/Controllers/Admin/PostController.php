<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::query();

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $posts = $query->orderByDesc('published_at')->paginate(15)->withQueryString();

        if ($request->header('X-LiveSearch') === '1') {
            return view('admin.posts._table', compact('posts'));
        }

        return view('admin.posts.index', compact('posts'));
    }

    public function create()
    {
        return view('admin.posts.form', ['post' => new Post]);
    }

    public function store(Request $request, ActivityLogger $logger)
    {
        $data = $this->validated($request);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('posts', 'cloudflare');
        }

        $post = Post::create($data);

        $logger->record('post.created', $post, "Post {$post->title} created.");

        return redirect()->route('admin.posts.index')
            ->with('success', 'Post created successfully.');
    }

    public function edit(Post $post)
    {
        return view('admin.posts.form', compact('post'));
    }

    public function update(Request $request, Post $post, ActivityLogger $logger)
    {
        $data = $this->validated($request, $post);

        if ($request->hasFile('cover_image')) {
            if ($post->cover_image) {
                Storage::disk('cloudflare')->delete($post->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('posts', 'cloudflare');
        }

        $post->update($data);

        $logger->record('post.updated', $post, "Post {$post->title} updated.");

        return redirect()->route('admin.posts.index')
            ->with('success', 'Post updated successfully.');
    }

    public function destroy(Post $post, ActivityLogger $logger)
    {
        if ($post->cover_image) {
            Storage::disk('cloudflare')->delete($post->cover_image);
        }
        $post->delete();

        $logger->record('post.deleted', $post, "Post {$post->title} deleted.");

        return redirect()->route('admin.posts.index')
            ->with('success', 'Post deleted successfully.');
    }

    /**
     * Shared validation for create/update. A blank published_at means "publish
     * now" on create and "mark as draft" on update, so admins can schedule.
     */
    private function validated(Request $request, ?Post $post = null): array
    {
        $data = $request->validate([
            'title' => 'required|max:255',
            'slug' => 'nullable|max:255|unique:posts,slug'.($post ? ','.$post->id : ''),
            'excerpt' => 'nullable|max:1000',
            'body' => 'nullable',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp',
            'is_active' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        if ($request->filled('published_at')) {
            $data['published_at'] = \Illuminate\Support\Carbon::parse($request->input('published_at'));
        } elseif ($post === null) {
            $data['published_at'] = now();
        } else {
            $data['published_at'] = null;
        }

        return $data;
    }
}
