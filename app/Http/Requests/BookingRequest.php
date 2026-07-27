<?php

namespace App\Http\Requests;

use App\Models\Cottage;
use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'booking_type' => ['required', 'string', 'in:day_tour,overnight'],
            'cottage_id' => ['required', 'exists:cottages,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today', $this->validateAvailability()],
            'check_out' => ['nullable', 'required_if:booking_type,overnight', 'date', 'after_or_equal:check_in'],
            'pax' => ['required', 'integer', 'min:1', 'max:50'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'check_out.required_if' => 'Check-out date is required for overnight bookings.',
            'check_out.after_or_equal' => 'Check-out must be on or after check-in.',
        ];
    }

    private function validateAvailability(): ?\Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            $cottageId = $this->input('cottage_id');
            if (!$cottageId || !$value) return;

            $cottage = Cottage::find($cottageId);
            if (!$cottage) return;

            $checkOut = $this->input('check_out') ?? $value;

            $blockedDates = $cottage->dateBlocks()
                ->whereBetween('date', [$value, $checkOut])
                ->pluck('date')
                ->map(fn ($d) => $d->format('M d, Y'))
                ->implode(', ');

            if ($blockedDates) {
                $fail("The cottage is not available on: {$blockedDates}.");
            }
        };
    }
}
