<?php

namespace App\Http\Requests;

use App\Enums\RequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for a responsible staff member moving a request to the next status
 * (UC-08 steps 2–3). Authorization (request-scoped reach and responsible staff
 * via `RequestPolicy@review`) is enforced in the controller; this request
 * validates that the chosen target belongs to the defined status set.
 *
 * A status outside the six defined slugs is rejected as 422 (ext 2a) before the
 * transition guard is ever consulted; whether the move is legal in the v1
 * transition graph is a separate check the guard makes (409, ext 4a)
 * [03_use-cases.md UC-08 ext 2a; 02_business-rules.md BR-004;
 * docs/conventions.md API error responses — 422].
 */
class UpdateRequestStatusRequest extends FormRequest
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
            'status' => ['required', Rule::in(RequestStatus::values())],
        ];
    }
}
