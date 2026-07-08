<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for a responsible staff member's missing-information request
 * (UC-07 main flow, step 3). Authorization (request-scoped reach, responsible
 * staff via `RequestPolicy@review`, and the message participant guard via
 * `MessagePolicy@create`) is enforced in the controller; this request validates
 * the message shape.
 *
 * An empty message is not sent — the system asks for a clear message, so a blank
 * `body` fails validation as 422 (ext 3a) [03_use-cases.md UC-07 ext 3a;
 * docs/conventions.md API error responses — 422].
 */
class RequestMissingInformationRequest extends FormRequest
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
