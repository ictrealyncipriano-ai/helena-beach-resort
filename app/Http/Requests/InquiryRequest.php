<?php

namespace App\Http\Requests;

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
            'check_in' => ['nullable', 'date'],
            'check_out' => ['nullable', 'date', 'after_or_equal:check_in'],
            'pax' => ['nullable', 'integer', 'min:1'],
            'message' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'check_out.after_or_equal' => 'Check-out must be on or after check-in.',
            'cottage_id.exists' => 'The selected cottage is not available.',
        ];
    }
}
