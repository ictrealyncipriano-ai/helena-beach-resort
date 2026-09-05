<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for admin walk-in inquiry create/update. Shared by store() and
 * update() so both paths enforce identical rules. check_out is allowed to
 * equal check_in (a same-day day tour) but must never precede it.
 */
class InquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Role check is enforced by AdminMiddleware; require auth here so the
        // request can never be used outside the admin guard.
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|max:20',
            'guest_id' => 'nullable|exists:guests,id',
            'booking_type' => 'nullable|in:day_tour,overnight',
            'check_in' => 'nullable|date',
            'check_out' => 'nullable|date|after_or_equal:check_in',
            'pax' => 'nullable|integer|min:1|max:50',
            'total_amount' => 'nullable|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'cottage_id' => 'nullable|exists:cottages,id',
            'status' => 'required|in:pending,confirmed,cancelled,expired',
            'message' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'check_out.after_or_equal' => 'Check-out must be on or after check-in.',
        ];
    }
}