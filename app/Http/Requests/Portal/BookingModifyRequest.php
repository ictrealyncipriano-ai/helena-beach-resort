<?php

namespace App\Http\Requests\Portal;

use App\Models\Inquiry;
use App\Rules\CottageAvailability;
use Illuminate\Foundation\Http\FormRequest;

class BookingModifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $inquiryId = $this->route('inquiry') instanceof Inquiry
            ? $this->route('inquiry')->id
            : null;

        return [
            'booking_type' => 'required|in:day_tour,overnight',
            'cottage_id' => 'required|exists:cottages,id,is_available,1',
            'check_in' => ['required', 'date', 'after_or_equal:today', new CottageAvailability(
                $this->input('cottage_id'),
                $this->route('inquiry') instanceof Inquiry ? $this->route('inquiry')->email : null,
                $this->input('booking_type'),
                $this->input('check_out'),
                false,
                $inquiryId,
            )],
            'check_out' => 'nullable|required_if:booking_type,overnight|date|after:check_in',
            'pax' => 'required|integer|min:1|max:50',
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
