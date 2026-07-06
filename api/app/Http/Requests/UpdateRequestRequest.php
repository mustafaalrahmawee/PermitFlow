<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for editing a draft before submission (UC-02 main flow, steps 3–6;
 * ext 6a). Authorization (owner + editable status, and out-of-scope reported as
 * not found) is enforced in the controller, so this request validates shape only.
 *
 * `request_details` is whole-block JSON — the draft is written as one block — so
 * the editable fields are supplied together. The same active-category rules as
 * creation apply: a missing or non-scalar `request_category_id` (ext 3a) and an
 * unknown or inactive category (ext 3b) surface as 422
 * [04_data-model.md §3.1; 02_business-rules.md BR-002;
 * docs/conventions.md API error responses — 422].
 */
class UpdateRequestRequest extends FormRequest
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
            'request_category_id' => [
                'required',
                'integer',
                Rule::exists('request_categories', 'id')->where('is_active', true),
            ],
            'title' => ['required', 'string', 'max:255'],
            'request_details' => ['required', 'array'],
        ];
    }
}
