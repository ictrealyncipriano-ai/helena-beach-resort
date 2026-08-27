<?php

namespace App\Http\Requests;

use App\Rules\CottageAvailability;
use Illuminate\Foundation\Http\FormRequest;

class InquiryRequest extends FormRequest
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
            'cottage_id' => ['nullable', 'exists:cottages,id'],
            'check_in' => ['nullable', 'date', new CottageAvailability(
                $this->input('cottage_id'),
                $this->input('email'),
                $this->input('booking_type'),
                $this->input('check_out'),
            )],
            'check_out' => ['nullable', 'date', 'after:check_in'],
            'pax' => ['nullable', 'integer', 'min:1', 'max:50'],
            'message' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'check_out.after' => 'Check-out must be after check-in.',
            'cottage_id.exists' => 'The selected cottage is not available.',
        ];
    }
}
