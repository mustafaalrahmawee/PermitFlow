<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for a request participant sending a general message (UC-10 main
 * flow, step 3). Authorization (request-scoped reach and the message participant
 * guard via `MessagePolicy@create`) and the responsible-staff precondition
 * (BR-009/BR-011) are enforced in the controller; this request validates the
 * message shape.
 *
 * An empty message is not recorded — the primary actor is asked to enter message
 * content, so a blank `body` fails validation as 422 (ext 3a) [03_use-cases.md
 * UC-10 ext 3a; docs/conventions.md API error responses — 422].
 */
class StoreMessageRequest extends FormRequest
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
