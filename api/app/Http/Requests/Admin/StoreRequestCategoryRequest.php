<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for creating a request category (UC-11 main flow, steps 3–7).
 *
 * Authorization is enforced by the `manage-categories` route gate, so this
 * request authorizes at the shape level only. `name` is unique across
 * categories, `description` is optional, and `is_active` decides availability
 * for future request filing. A malformed payload fails validation as 422
 * [docs/conventions.md API error responses — 422].
 */
class StoreRequestCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('request_categories', 'name')],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
