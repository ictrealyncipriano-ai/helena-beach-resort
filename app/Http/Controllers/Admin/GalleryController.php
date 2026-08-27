<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Services\ActivityLogger;
use App\Traits\ManagesCloudflareFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    use ManagesCloudflareFiles;
    public function index(Request $request)
    {
        $query = Gallery::query();

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $galleries = $query->orderBy('sort_order')->paginate(self::ADMIN_PER_PAGE)->withQueryString();

        $galleriesData = $galleries->map(fn ($gallery) => [
            'id' => $gallery->id,
            'title' => $gallery->title,
            'category' => $gallery->category,
            'sort_order' => $gallery->sort_order,
            'is_active' => $gallery->is_active,
            'photo_url' => $gallery->photo_path ? Storage::url($gallery->photo_path) : null,
        ])->values();

        return view('admin.gallery.index', compact('galleries', 'galleriesData'));
    }

    public function create()
    {
        return view('admin.gallery.form', ['gallery' => new Gallery]);
    }

    public function store(Request $request, ActivityLogger $logger)
    {
        $data = $this->validated($request);
        $data['photo_path'] = $request->file('photo_path')->store('gallery', 'cloudflare');

        $gallery = Gallery::create($data);

        $logger->record('gallery.created', $gallery, "Gallery image {$gallery->title} added.", [
            'category' => $gallery->category,
        ]);

        return redirect()->route('admin.gallery.index')
            ->with('success', 'Gallery image added successfully.');
    }

    public function edit(Gallery $gallery)
    {
        return view('admin.gallery.form', compact('gallery'));
    }

    public function update(Request $request, Gallery $gallery, ActivityLogger $logger)
    {
        $data = $this->validated($request, $gallery);

        if ($request->hasFile('photo_path')) {
            $this->deleteFromCloudflare($gallery->photo_path);
            $data['photo_path'] = $request->file('photo_path')->store('gallery', 'cloudflare');
        }

        $gallery->update($data);

        $logger->record('gallery.updated', $gallery, "Gallery image {$gallery->title} updated.", [
            'category' => $gallery->category,
        ]);

        return redirect()->route('admin.gallery.index')
            ->with('success', 'Gallery image updated successfully.');
    }

    public function destroy(Gallery $gallery, ActivityLogger $logger)
    {
        $this->deleteFromCloudflare($gallery->photo_path);
        $gallery->delete();

        $logger->record('gallery.deleted', $gallery, "Gallery image {$gallery->title} deleted.", [
            'category' => $gallery->category,
        ]);

        return redirect()->route('admin.gallery.index')
            ->with('success', 'Gallery image deleted successfully.');
    }

    private function validated(Request $request, ?Gallery $gallery = null): array
    {
        $data = $request->validate([
            'title' => 'nullable|max:255',
            'photo_path' => ($gallery ? 'nullable' : 'required').'|image|mimes:jpeg,png,jpg,webp|max:5120',
            'category' => 'nullable|in:Resort,Beach,Food,Events',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
