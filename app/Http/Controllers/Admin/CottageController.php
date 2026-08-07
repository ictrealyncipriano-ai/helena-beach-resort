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
            'slug' => 'nullable|max:255|unique:cottages,slug',
            'description' => 'nullable',
            'capacity' => 'nullable|integer|min:0',
            'rate_daytour' => 'nullable|numeric|min:0',
            'rate_overnight' => 'nullable|numeric|min:0',
            'is_available' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            // Uploads are validated as real images (magic-bytes checked) and
            // capped at 5 MB; stored files get a random name + content-derived
            // extension so a malicious filename can never reach the disk.
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            // Nested arrays are validated and whitelisted below so a crafted
            // payload can never mass-assign inquiry_id onto a date block.
            'amenities' => 'nullable|array',
            'amenities.*.name' => 'nullable|string|max:255',
            'amenities.*.icon' => 'nullable|string|max:255',
            'date_blocks' => 'nullable|array',
            'date_blocks.*.date' => 'nullable|date_format:Y-m-d',
            'date_blocks.*.reason' => 'nullable|string|max:255',
        ]);

        $data['is_available'] = $request->boolean('is_available');
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        $cottage = Cottage::create($data);

        if ($request->has('amenities')) {
            foreach ($request->input('amenities', []) as $amenity) {
                if (!empty($amenity['name'])) {
                    $cottage->amenities()->create([
                        'name' => $amenity['name'],
                        'icon' => $amenity['icon'] ?? null,
                    ]);
                }
            }
        }

        if ($request->has('date_blocks')) {
            foreach ($request->input('date_blocks', []) as $block) {
                if (!empty($block['date'])) {
                    $cottage->dateBlocks()->create([
                        'date' => $block['date'],
                        'reason' => $block['reason'] ?? null,
                    ]);
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
            'slug' => 'nullable|max:255|unique:cottages,slug,' . $cottage->id,
            'description' => 'nullable',
            'capacity' => 'nullable|integer|min:0',
            'rate_daytour' => 'nullable|numeric|min:0',
            'rate_overnight' => 'nullable|numeric|min:0',
            'is_available' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            // Same upload rules as store(); delete_photos is a comma-separated
            // string of integer ids (see admin cottage form).
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'delete_photos' => 'nullable|string|regex:/^[0-9,]*$/',
            // Nested arrays are validated and whitelisted below so a crafted
            // payload can never mass-assign inquiry_id onto a date block.
            'amenities' => 'nullable|array',
            'amenities.*.name' => 'nullable|string|max:255',
            'amenities.*.icon' => 'nullable|string|max:255',
            'date_blocks' => 'nullable|array',
            'date_blocks.*.date' => 'nullable|date_format:Y-m-d',
            'date_blocks.*.reason' => 'nullable|string|max:255',
        ]);

        $data['is_available'] = $request->boolean('is_available');
        $data['slug'] = $data['slug'] ?: $cottage->slug;
        $cottage->update($data);

        $cottage->amenities()->delete();
        if ($request->has('amenities')) {
            foreach ($request->input('amenities', []) as $amenity) {
                if (!empty($amenity['name'])) {
                    $cottage->amenities()->create([
                        'name' => $amenity['name'],
                        'icon' => $amenity['icon'] ?? null,
                    ]);
                }
            }
        }

        // Delete only non-inquiry date blocks. Blocks whose reason contains
        // "HB-" are held by live inquiries (Pending:/Booked:) and must never
        // be wiped by a cottage edit — otherwise a confirmed guest's dates
        // could be silently freed.
        $cottage->dateBlocks()
            ->where(fn ($q) => $q->whereNull('reason')->orWhere('reason', 'not like', '%HB-%'))
            ->delete();

        if ($request->has('date_blocks')) {
            foreach ($request->input('date_blocks', []) as $block) {
                if (empty($block['date'])) {
                    continue;
                }

                // Defense in depth: never touch a block that an inquiry holds.
                $existing = $cottage->dateBlocks()->where('date', $block['date'])->first();
                if ($existing && str_contains((string) $existing->reason, 'HB-')) {
                    continue;
                }

                $cottage->dateBlocks()->updateOrCreate(
                    ['date' => $block['date']],
                    ['reason' => $block['reason'] ?? null]
                );
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

        if ($request->filled('delete_photos')) {
            // Only integer ids, and only photos belonging to THIS cottage, so
            // a crafted id list can never delete another cottage's images.
            $ids = array_values(array_filter(array_map('intval', explode(',', $request->input('delete_photos')))));

            if ($ids !== []) {
                $photos = CottagePhoto::where('cottage_id', $cottage->id)->whereIn('id', $ids)->get();
                foreach ($photos as $p) {
                    Storage::disk('cloudflare')->delete($p->photo_path);
                    $p->delete();
                }
            }
        }

        return redirect()->route('admin.cottages.index')
            ->with('success', 'Cottage updated successfully.');
    }

    public function destroy(Cottage $cottage)
    {
        // Never delete a cottage that still holds dates for a live booking:
        // the cascade would silently destroy the date blocks (and the hold)
        // of pending/confirmed inquiries. Those must be cancelled first.
        $activeBlocks = $cottage->dateBlocks()
            ->whereHas('inquiry', fn ($q) => $q->whereIn('status', ['pending', 'confirmed']))
            ->exists();

        if ($activeBlocks) {
            return back()->with('error', 'This cottage has active bookings. Cancel or complete them before deleting the cottage.');
        }

        foreach ($cottage->photos as $photo) {
            Storage::disk('cloudflare')->delete($photo->photo_path);
        }
        $cottage->delete();

        return redirect()->route('admin.cottages.index')
            ->with('success', 'Cottage deleted successfully.');
    }
}
