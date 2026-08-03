<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BookingCancelled;
use App\Mail\BookingConfirmed;
use App\Models\Cottage;
use App\Models\Guest;
use App\Models\Inquiry;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class InquiryController extends Controller
{
    public function index(Request $request)
    {
        $query = Inquiry::with(['cottage', 'guest']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('reference_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('booking_type')) {
            $query->where('booking_type', $request->booking_type);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('cottage_id')) {
            $query->where('cottage_id', $request->cottage_id);
        }

        $inquiries = $query->latest()->paginate(15);
        $cottages = Cottage::pluck('name', 'id');

        return view('admin.inquiries.index', compact('inquiries', 'cottages'));
    }

    public function show(Inquiry $inquiry)
    {
        $inquiry->load(['cottage', 'guest']);

        return view('admin.inquiries.show', compact('inquiry'));
    }

    public function edit(Inquiry $inquiry)
    {
        $inquiry->load(['cottage', 'guest']);
        $cottages = Cottage::pluck('name', 'id');
        $guests = Guest::pluck('name', 'id');

        return view('admin.inquiries.form', compact('inquiry', 'cottages', 'guests'));
    }

    public function update(Request $request, Inquiry $inquiry)
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|max:255',
            'guest_id' => 'nullable|exists:guests,id',
            'booking_type' => 'nullable|in:day_tour,overnight',
            'check_in' => 'nullable|date',
            'check_out' => 'nullable|date',
            'pax' => 'nullable|integer|min:1',
            'total_amount' => 'nullable|numeric|min:0',
            'cottage_id' => 'nullable|exists:cottages,id',
            'status' => 'required|in:pending,confirmed,cancelled,expired',
            'message' => 'nullable',
        ]);

        $original = [
            'cottage_id' => $inquiry->cottage_id,
            'check_in' => $inquiry->check_in?->format('Y-m-d'),
            'check_out' => $inquiry->check_out?->format('Y-m-d'),
        ];

        $inquiry->update($data);
        $inquiry->refresh();

        // Release the blocks held for the original schedule, then re-hold
        // for the new schedule so stale blocks never linger.
        $inquiry->releaseBlocks($original);

        if ($inquiry->status === 'confirmed') {
            $inquiry->bookBlocks();
        } elseif ($inquiry->status === 'pending') {
            $inquiry->reserveBlocks();
        }

        return redirect()->route('admin.inquiries.index')
            ->with('success', 'Inquiry updated successfully.');
    }

    public function destroy(Inquiry $inquiry)
    {
        $inquiry->releaseBlocks();
        $inquiry->delete();

        return redirect()->route('admin.inquiries.index')
            ->with('success', 'Inquiry deleted successfully.');
    }

    public function confirm(Inquiry $inquiry)
    {
        if ($inquiry->status !== 'pending') {
            return back()->with('error', 'Only pending inquiries can be confirmed.');
        }

        $inquiry->update(['status' => 'confirmed']);
        $inquiry->bookBlocks();

        if ($inquiry->guest) {
            $inquiry->guest->increment('total_stays');
            $inquiry->guest->update(['last_stay_at' => now()]);
        }

        try {
            Mail::to($inquiry->email)->send(new BookingConfirmed($inquiry));
        } catch (\Exception $e) {
            // Log error but don't break the flow
        }

        return redirect()->route('admin.inquiries.index')
            ->with('success', "Booking {$inquiry->reference_code} confirmed successfully.");
    }

    public function cancel(Inquiry $inquiry)
    {
        if ($inquiry->status !== 'pending') {
            return back()->with('error', 'Only pending inquiries can be cancelled.');
        }

        $inquiry->update(['status' => 'cancelled']);
        $inquiry->releaseBlocks();

        if ($inquiry->guest && $inquiry->guest->total_stays > 0) {
            $inquiry->guest->decrement('total_stays');
        }

        try {
            Mail::to($inquiry->email)->send(new BookingCancelled($inquiry));

            $ownerEmail = SiteSetting::getValue('contact_email');
            if ($ownerEmail) {
                Mail::to($ownerEmail)->send(new BookingCancelled($inquiry));
            }
        } catch (\Exception $e) {
            // Log error
        }

        return redirect()->route('admin.inquiries.index')
            ->with('success', "Booking {$inquiry->reference_code} cancelled successfully.");
    }
}
