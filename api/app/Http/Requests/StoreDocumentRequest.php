<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for attaching a supporting document to a draft (UC-02 main flow,
 * step 5). Authorization (owner + editable status, out-of-scope reported as not
 * found) is enforced in the controller; this request validates the upload shape.
 *
 * A payload that is not a usable supporting file — no file, or a rejected type —
 * fails validation as 422 (ext 5a). The file bytes go to the S3/MinIO disk in the
 * controller; a store fault there is a 500 that leaves the draft intact (ext 5b)
 * [03_use-cases.md UC-02 ext 5a/5b; 02_business-rules.md BR-006;
 * docs/conventions.md Storage; API error responses — 422].
 */
class StoreDocumentRequest extends FormRequest
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
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
