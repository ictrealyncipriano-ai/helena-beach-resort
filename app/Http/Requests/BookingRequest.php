<?php

namespace App\Http\Requests;

use App\Rules\CottageAvailability;
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
            'check_in' => ['required', 'date', 'after_or_equal:today', new CottageAvailability(
                $this->input('cottage_id'),
                $this->input('email'),
                $this->input('booking_type'),
                $this->input('check_out'),
                true,
            )],
            'check_out' => ['nullable', 'required_if:booking_type,overnight', 'date', 'after:check_in'],
            'pax' => ['required', 'integer', 'min:1', 'max:50'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'check_out.required_if' => 'Check-out date is required for overnight bookings.',
            'check_out.after' => 'Check-out must be after check-in.',
        ];
    }
}
