<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for creating a request as a draft (UC-02 main flow, steps 1–4).
 *
 * Authorization is the route's `auth:sanctum` guard: the draft is owned by the
 * caller, so there is no request-scoped policy to evaluate at creation time.
 * Exactly one active category must be named — `integer` rejects a non-scalar or
 * a multi-value selection (ext 3a), and the active-only `exists` rule rejects an
 * unknown or inactive category (ext 3b); both surface as 422
 * [03_use-cases.md UC-02 ext 3a/3b; 02_business-rules.md BR-002;
 * docs/conventions.md API error responses — 422].
 */
class StoreRequestRequest extends FormRequest
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
