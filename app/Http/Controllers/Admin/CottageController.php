<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cottage;
use App\Models\CottagePhoto;
use App\Services\ActivityLogger;
use App\Traits\ManagesCloudflareFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CottageController extends Controller
{
    use ManagesCloudflareFiles;
    public function index(Request $request): View
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

        $cottages = $query->orderBy('sort_order')->paginate(self::ADMIN_PER_PAGE)->withQueryString();

        $cottagesData = $cottages->map(function ($cottage) {
            return [
                'id' => $cottage->id,
                'name' => $cottage->name,
                'slug' => $cottage->slug,
                'description' => $cottage->description,
                'capacity' => $cottage->capacity,
                'rate_daytour' => $cottage->rate_daytour,
                'rate_overnight' => $cottage->rate_overnight,
                'peak_start' => $cottage->peak_start?->format('Y-m-d'),
                'peak_end' => $cottage->peak_end?->format('Y-m-d'),
                'peak_rate_daytour' => $cottage->peak_rate_daytour,
                'peak_rate_overnight' => $cottage->peak_rate_overnight,
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

    public function create(): View
    {
        return view('admin.cottages.form', ['cottage' => new Cottage]);
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $this->validated($request);

        $this->validatePeakPricing($request);

        $data['is_available'] = $request->boolean('is_available');
        $data['slug'] = ! empty($data['slug']) ? $data['slug'] : Str::slug($data['name']);

        $cottage = Cottage::create($data);

        $this->syncAmenities($cottage, $request);
        $this->syncDateBlocks($cottage, $request);
        $this->syncPhotos($cottage, $request);

        $logger->record('cottage.created', $cottage, "Cottage {$cottage->name} created.", [
            'capacity' => $cottage->capacity,
            'rate_overnight' => $cottage->rate_overnight,
        ]);

        return redirect()->route('admin.cottages.index')
            ->with('success', 'Cottage created successfully.');
    }

    public function edit(Cottage $cottage): View
    {
        $cottage->load(['amenities', 'photos', 'dateBlocks']);

        return view('admin.cottages.form', compact('cottage'));
    }

    public function update(Request $request, Cottage $cottage, ActivityLogger $logger): RedirectResponse
    {
        $data = $this->validated($request, $cottage);

        $this->validatePeakPricing($request);

        $data['is_available'] = $request->boolean('is_available');
        $data['slug'] = $data['slug'] ?: $cottage->slug;
        $cottage->update($data);

        $this->syncAmenities($cottage, $request);
        $this->syncDateBlocks($cottage, $request, true);
        $this->syncPhotos($cottage, $request);

        $logger->record('cottage.updated', $cottage, "Cottage {$cottage->name} updated.", [
            'capacity' => $cottage->capacity,
            'rate_overnight' => $cottage->rate_overnight,
        ]);

        return redirect()->route('admin.cottages.index')
            ->with('success', 'Cottage updated successfully.');
    }

    /**
     * Replace the cottage's amenities from the submitted form. Empty amenity
     * names are skipped.
     */
    private function syncAmenities(Cottage $cottage, Request $request): void
    {
        if ($request->has('amenities')) {
            foreach ($request->input('amenities', []) as $amenity) {
                if (! empty($amenity['name'])) {
                    $cottage->amenities()->create([
                        'name' => $amenity['name'],
                        'icon' => $amenity['icon'] ?? null,
                    ]);
                }
            }
        }
    }

    /**
     * Apply the submitted date blocks. On update the existing non-inquiry
     * blocks are deleted first, and with $protectInquiryBlocks a block held
     * by a live inquiry (reason contains "HB-") is never touched.
     */
    private function syncDateBlocks(Cottage $cottage, Request $request, bool $protectInquiryBlocks = false): void
    {
        if (! $request->has('date_blocks')) {
            return;
        }

        if ($protectInquiryBlocks) {
            // Delete only non-inquiry date blocks. Blocks whose reason contains
            // "HB-" are held by live inquiries (Pending:/Booked:) and must never
            // be wiped by a cottage edit — otherwise a confirmed guest's dates
            // could be silently freed.
            $cottage->dateBlocks()
                ->where(fn ($q) => $q->whereNull('reason')->orWhere('reason', 'not like', '%HB-%'))
                ->delete();
        }

        foreach ($request->input('date_blocks', []) as $block) {
            if (empty($block['date'])) {
                continue;
            }

            if ($protectInquiryBlocks) {
                // Defense in depth: never touch a block that an inquiry holds.
                $existing = $cottage->dateBlocks()->where('date', $block['date'])->first();
                if ($existing && str_contains((string) $existing->reason, 'HB-')) {
                    continue;
                }

                $cottage->dateBlocks()->updateOrCreate(
                    ['date' => $block['date']],
                    ['reason' => $block['reason'] ?? null]
                );
            } else {
                $cottage->dateBlocks()->create([
                    'date' => $block['date'],
                    'reason' => $block['reason'] ?? null,
                ]);
            }
        }
    }

    /**
     * Upload new photos from the form, and (on update) delete the photos
     * marked for removal. Only integer ids belonging to THIS cottage are
     * honored, so a crafted id list can never delete another cottage's images.
     */
    private function syncPhotos(Cottage $cottage, Request $request): void
    {
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
                    $this->deleteFromCloudflare($p->photo_path);
                    $p->delete();
                }
            }
        }
    }

    /**
     * Validate peak pricing consistency: start/end must both be set or both
     * null, and at least one peak rate must be positive when the window is set.
     */
    private function validatePeakPricing(Request $request): void
    {
        $start = $request->input('peak_start');
        $end = $request->input('peak_end');
        $rateDaytour = $request->input('peak_rate_daytour');
        $rateOvernight = $request->input('peak_rate_overnight');

        if ($start && ! $end) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'peak_end' => 'Peak end date is required when peak start is set.',
            ]);
        }

        if (! $start && $end) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'peak_start' => 'Peak start date is required when peak end is set.',
            ]);
        }

        if ($start && $end) {
            if ((float) ($rateDaytour ?? 0) <= 0 && (float) ($rateOvernight ?? 0) <= 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'peak_rate_daytour' => 'At least one peak rate must be greater than 0 when peak window is set.',
                ]);
            }
        }
    }

    private function validated(Request $request, ?Cottage $cottage = null): array
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'slug' => 'nullable|max:255|unique:cottages,slug'.($cottage ? ','.$cottage->id : ''),
            'description' => 'nullable',
            'capacity' => 'nullable|integer|min:0',
            'rate_daytour' => 'nullable|numeric|min:0',
            'rate_overnight' => 'nullable|numeric|min:0',
            'peak_start' => 'nullable|date',
            'peak_end' => 'nullable|date',
            'peak_rate_daytour' => 'nullable|numeric|min:0',
            'peak_rate_overnight' => 'nullable|numeric|min:0',
            'is_available' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'delete_photos' => $cottage ? 'nullable|string|regex:/^[0-9,]*$/' : 'prohibited',
            'amenities' => 'nullable|array',
            'amenities.*.name' => 'nullable|string|max:255',
            'amenities.*.icon' => 'nullable|string|max:255',
            'date_blocks' => 'nullable|array',
            'date_blocks.*.date' => 'nullable|date_format:Y-m-d',
            'date_blocks.*.reason' => 'nullable|string|max:255',
        ]);

        $data['is_available'] = $request->boolean('is_available');

        return $data;
    }

    public function destroy(Cottage $cottage, ActivityLogger $logger): RedirectResponse
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
            $this->deleteFromCloudflare($photo->photo_path);
        }
        $cottage->delete();

        $logger->record('cottage.deleted', $cottage, "Cottage {$cottage->name} deleted.");

        return redirect()->route('admin.cottages.index')
            ->with('success', 'Cottage deleted successfully.');
    }
}
