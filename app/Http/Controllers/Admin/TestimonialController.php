<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestimonialController extends Controller
{
    public function index(Request $request)
    {
        $query = Testimonial::with('cottage');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('guest_name', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        $testimonials = $query->orderBy('sort_order')->paginate(15);

        $testimonialsData = $testimonials->map(function ($testimonial) {
            return [
                'id' => $testimonial->id,
                'guest_name' => $testimonial->guest_name,
                'rating' => $testimonial->rating,
                'content' => $testimonial->content,
                'cottage_id' => $testimonial->cottage_id,
                'guest_avatar' => $testimonial->guest_avatar ? Storage::url($testimonial->guest_avatar) : null,
                'is_active' => (bool) $testimonial->is_active,
                'sort_order' => $testimonial->sort_order,
            ];
        })->values();

        $cottages = \App\Models\Cottage::pluck('name', 'id');

        return view('admin.testimonials.index', compact('testimonials', 'testimonialsData', 'cottages'));
    }

    public function create()
    {
        $cottages = \App\Models\Cottage::pluck('name', 'id');
        return view('admin.testimonials.form', ['testimonial' => new Testimonial, 'cottages' => $cottages]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'guest_name' => 'required|max:255',
            'content' => 'required',
            'rating' => 'required|integer|min:1|max:5',
            'cottage_id' => 'nullable|exists:cottages,id',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'guest_avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('guest_avatar')) {
            $data['guest_avatar'] = $request->file('guest_avatar')->store('testimonials', 'cloudflare');
        }

        Testimonial::create($data);

        return redirect()->route('admin.testimonials.index')
            ->with('success', 'Testimonial created successfully.');
    }

    public function edit(Testimonial $testimonial)
    {
        $cottages = \App\Models\Cottage::pluck('name', 'id');
        return view('admin.testimonials.form', compact('testimonial', 'cottages'));
    }

    public function update(Request $request, Testimonial $testimonial)
    {
        $data = $request->validate([
            'guest_name' => 'required|max:255',
            'content' => 'required',
            'rating' => 'required|integer|min:1|max:5',
            'cottage_id' => 'nullable|exists:cottages,id',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'guest_avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('guest_avatar')) {
            if ($testimonial->guest_avatar) {
                Storage::disk('cloudflare')->delete($testimonial->guest_avatar);
            }
            $data['guest_avatar'] = $request->file('guest_avatar')->store('testimonials', 'cloudflare');
        }

        $testimonial->update($data);

        return redirect()->route('admin.testimonials.index')
            ->with('success', 'Testimonial updated successfully.');
    }

    public function destroy(Testimonial $testimonial)
    {
        if ($testimonial->guest_avatar) {
            Storage::disk('cloudflare')->delete($testimonial->guest_avatar);
        }
        $testimonial->delete();

        return redirect()->route('admin.testimonials.index')
            ->with('success', 'Testimonial deleted successfully.');
    }
}
