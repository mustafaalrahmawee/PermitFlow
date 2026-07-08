<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for a citizen's reply to a missing-information request (UC-04 main
 * flow, steps 3–5). Authorization (request-scoped reach, owner + Draft/Waiting
 * for Citizen via `RequestPolicy@provideInformation`, and the message
 * participant guard via `MessagePolicy@create`) is enforced in the controller;
 * this request validates the reply shape.
 *
 * An empty reply carries no information, so a blank `body` fails validation as
 * 422 [03_use-cases.md UC-04 step 5; docs/conventions.md API error responses —
 * 422].
 */
class ProvideInformationRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
