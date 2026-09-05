<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for admin report export filters.
 * Ensures from/to are real dates so they can be safely echoed in report headers.
 */
class ExportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'from' => 'nullable|date_format:Y-m-d|before_or_equal:to',
            'to' => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'status' => 'nullable|in:pending,confirmed,cancelled,expired',
        ];
    }
}
