<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
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

        $galleries = $query->orderBy('sort_order')->paginate(15);
        return view('admin.gallery.index', compact('galleries'));
    }

    public function create()
    {
        return view('admin.gallery.form', ['gallery' => new Gallery]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'nullable|max:255',
            'photo_path' => 'required|image|mimes:jpeg,png,jpg,webp',
            'category' => 'nullable|in:Resort,Beach,Food,Events',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['photo_path'] = $request->file('photo_path')->store('gallery', 'cloudflare');

        Gallery::create($data);

        return redirect()->route('admin.gallery.index')
            ->with('success', 'Gallery image added successfully.');
    }

    public function edit(Gallery $gallery)
    {
        return view('admin.gallery.form', compact('gallery'));
    }

    public function update(Request $request, Gallery $gallery)
    {
        $data = $request->validate([
            'title' => 'nullable|max:255',
            'photo_path' => 'nullable|image|mimes:jpeg,png,jpg,webp',
            'category' => 'nullable|in:Resort,Beach,Food,Events',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('photo_path')) {
            if ($gallery->photo_path) {
                Storage::disk('cloudflare')->delete($gallery->photo_path);
            }
            $data['photo_path'] = $request->file('photo_path')->store('gallery', 'cloudflare');
        }

        $gallery->update($data);

        return redirect()->route('admin.gallery.index')
            ->with('success', 'Gallery image updated successfully.');
    }

    public function destroy(Gallery $gallery)
    {
        if ($gallery->photo_path) {
            Storage::disk('cloudflare')->delete($gallery->photo_path);
        }
        $gallery->delete();

        return redirect()->route('admin.gallery.index')
            ->with('success', 'Gallery image deleted successfully.');
    }
}
