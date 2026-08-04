<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function index(Request $request)
    {
        $query = Guest::withCount('inquiries');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $guests = $query->latest()->paginate(15);
        return view('admin.guests.index', compact('guests'));
    }

    public function show(Guest $guest)
    {
        $guest->load('inquiries.cottage');

        $inquiries = $guest->inquiries;
        $paidCount = $inquiries->filter(fn ($i) => $i->isPaid())->count();
        $paidAmount = $inquiries->filter(fn ($i) => $i->isPaid())->sum('paid_amount');
        $failedCount = $inquiries->filter(fn ($i) => $i->hasFailedPayment())->count();
        $refundedCount = $inquiries->filter(fn ($i) => $i->isRefunded())->count();

        return view('admin.guests.show', compact(
            'guest',
            'paidCount',
            'paidAmount',
            'failedCount',
            'refundedCount',
        ));
    }

    public function edit(Guest $guest)
    {
        return view('admin.guests.form', compact('guest'));
    }

    public function update(Request $request, Guest $guest)
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:guests,email,' . $guest->id,
            'phone' => 'nullable|max:20',
            'notes' => 'nullable',
        ]);

        $guest->update($data);

        return redirect()->route('admin.guests.index')
            ->with('success', 'Guest updated successfully.');
    }

    public function destroy(Guest $guest)
    {
        $guest->delete();
        return redirect()->route('admin.guests.index')
            ->with('success', 'Guest deleted successfully.');
    }
}
