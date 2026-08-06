<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cottage;
use App\Models\CottageAmenity;
use App\Models\CottageDateBlock;
use App\Models\CottagePhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CottageController extends Controller
{
    public function index(Request $request)
    {
        $query = Cottage::withCount('inquiries')->with(['primaryPhoto', 'amenities', 'photos', 'dateBlocks']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('availability')) {
            $query->where('is_available', $request->availability === 'available');
        }

        $cottages = $query->orderBy('sort_order')->paginate(15);

        $cottagesData = $cottages->map(function ($cottage) {
            return [
                'id' => $cottage->id,
                'name' => $cottage->name,
                'slug' => $cottage->slug,
                'description' => $cottage->description,
                'capacity' => $cottage->capacity,
                'rate_daytour' => $cottage->rate_daytour,
                'rate_overnight' => $cottage->rate_overnight,
                'sort_order' => $cottage->sort_order,
                'is_available' => (bool) $cottage->is_available,
                'amenities' => $cottage->amenities->map(fn ($a) => [
                    'name' => $a->name,
                    'icon' => $a->icon,
                ])->values(),
                'photos' => $cottage->photos->map(fn ($p) => [
                    'id' => $p->id,
                    'url' => Storage::url($p->photo_path),
                    'is_primary' => (bool) $p->is_primary,
                ])->values(),
                'date_blocks' => $cottage->dateBlocks->map(fn ($b) => [
                    'date' => $b->date?->format('Y-m-d'),
                    'reason' => $b->reason,
                ])->values(),
            ];
        })->values();

        return view('admin.cottages.index', compact('cottages', 'cottagesData'));
    }

    public function create()
    {
        return view('admin.cottages.form', ['cottage' => new Cottage]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'slug' => 'required|max:255|unique:cottages,slug',
            'description' => 'nullable',
            'capacity' => 'nullable|integer|min:0',
            'rate_daytour' => 'nullable|numeric|min:0',
            'rate_overnight' => 'nullable|numeric|min:0',
            'is_available' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data['is_available'] = $request->boolean('is_available');
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        $cottage = Cottage::create($data);

        if ($request->has('amenities')) {
            foreach ($request->input('amenities', []) as $amenity) {
                if (!empty($amenity['name'])) {
                    $cottage->amenities()->create($amenity);
                }
            }
        }

        if ($request->has('date_blocks')) {
            foreach ($request->input('date_blocks', []) as $block) {
                if (!empty($block['date'])) {
                    $cottage->dateBlocks()->create($block);
                }
            }
        }

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos', []) as $i => $photo) {
                $path = $photo->store('cottages', 'cloudflare');
                $cottage->photos()->create([
                    'photo_path' => $path,
                    'is_primary' => $request->input('primary_photo_index') == $i,
                    'sort_order' => $i,
                ]);
            }
        }

        return redirect()->route('admin.cottages.index')
            ->with('success', 'Cottage created successfully.');
    }

    public function edit(Cottage $cottage)
    {
        $cottage->load(['amenities', 'photos', 'dateBlocks']);
        return view('admin.cottages.form', compact('cottage'));
    }

    public function update(Request $request, Cottage $cottage)
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'slug' => 'required|max:255|unique:cottages,slug,' . $cottage->id,
            'description' => 'nullable',
            'capacity' => 'nullable|integer|min:0',
            'rate_daytour' => 'nullable|numeric|min:0',
            'rate_overnight' => 'nullable|numeric|min:0',
            'is_available' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data['is_available'] = $request->boolean('is_available');
        $cottage->update($data);

        $cottage->amenities()->delete();
        if ($request->has('amenities')) {
            foreach ($request->input('amenities', []) as $amenity) {
                if (!empty($amenity['name'])) {
                    $cottage->amenities()->create($amenity);
                }
            }
        }

        $cottage->dateBlocks()->delete();
        if ($request->has('date_blocks')) {
            foreach ($request->input('date_blocks', []) as $block) {
                if (!empty($block['date'])) {
                    $cottage->dateBlocks()->create($block);
                }
            }
        }

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos', []) as $i => $photo) {
                $path = $photo->store('cottages', 'cloudflare');
                $cottage->photos()->create([
                    'photo_path' => $path,
                    'is_primary' => $request->input('primary_photo_index') == $i,
                    'sort_order' => $i,
                ]);
            }
        }

        if ($request->input('delete_photos')) {
            $ids = explode(',', $request->input('delete_photos'));
            $photos = CottagePhoto::whereIn('id', $ids)->get();
            foreach ($photos as $p) {
                Storage::disk('cloudflare')->delete($p->photo_path);
                $p->delete();
            }
        }

        return redirect()->route('admin.cottages.index')
            ->with('success', 'Cottage updated successfully.');
    }

    public function destroy(Cottage $cottage)
    {
        foreach ($cottage->photos as $photo) {
            Storage::disk('cloudflare')->delete($photo->photo_path);
        }
        $cottage->delete();

        return redirect()->route('admin.cottages.index')
            ->with('success', 'Cottage deleted successfully.');
    }
}
