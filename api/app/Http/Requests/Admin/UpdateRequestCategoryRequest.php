<?php

namespace App\Http\Requests\Admin;

use App\Models\RequestCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for maintaining an existing request category (UC-11 main flow).
 *
 * PATCH is partial: each field is `sometimes`, but when present it must be
 * well-formed. The `name` uniqueness rule ignores the current row so a no-op
 * save is not a false 422. The used-category blockers (semantic rename) are
 * evaluated in the controller against the current record, not here.
 */
class UpdateRequestCategoryRequest extends FormRequest
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
        /** @var RequestCategory $requestCategory */
        $requestCategory = $this->route('requestCategory');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('request_categories', 'name')->ignore($requestCategory->id),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
