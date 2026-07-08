<?php

namespace App\Http\Requests;

use App\Enums\DecisionOutcome;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for a responsible staff member recording a decision (UC-09 steps
 * 3–4). Authorization (request-scoped reach and responsible staff via
 * `RequestPolicy@decide`) is enforced in the controller; this request validates
 * the decision outcome and the optional decision document.
 *
 * An outcome that is neither Approved nor Rejected is rejected as 422 (ext 3a)
 * before the transition guard is consulted, and an attached file that is not a
 * usable decision document is rejected as 422 (ext 4a). The bytes go to the
 * S3/MinIO disk in the controller; a store fault there is a 500 that records no
 * decision (ext 4b) [03_use-cases.md UC-09 ext 3a/4a/4b; 02_business-rules.md
 * BR-006, BR-008; docs/conventions.md Storage; API error responses — 422].
 */
class StoreDecisionRequest extends FormRequest
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
            'outcome' => ['required', Rule::in(DecisionOutcome::values())],
            'decision_text' => ['nullable', 'string', 'max:5000'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
