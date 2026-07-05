<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for maintaining the single organization-settings record (UC-12
 * main flow, steps 3–5).
 *
 * Authorization is enforced by the `manage-settings` route gate, so this request
 * authorizes at the shape level only. `settings_payload` is whole-block JSON: it
 * is validated and written as one block, never field-merged
 * [docs/conventions.md Data & schema — whole-block JSON].
 *
 * ext 5a — a changed setting that would conflict with v1 hard constraints is
 * rejected as 422 and the previous block stays active. The specs deliberately
 * leave the concrete settings unexpanded (contract Open Question), so the v1
 * hard constraints are pinned here against the seeded block shape (`locale`,
 * `timezone`): `locale` must be one of the supported UI locales and `timezone`
 * must be a valid IANA identifier. A block outside these bounds fails validation
 * [docs/conventions.md API error responses — 422].
 */
class UpdateOrganizationSettingsRequest extends FormRequest
{
    /**
     * Supported UI locales — a v1 hard constraint. Kept intentionally small; the
     * value set is an implementation decision, not a spec-enumerated list.
     *
     * @var array<int, string>
     */
    private const SUPPORTED_LOCALES = ['en', 'es', 'fr', 'de', 'ar'];

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
            'organization_name' => ['required', 'string', 'max:255'],
            'settings_payload' => ['required', 'array'],
            'settings_payload.locale' => ['required', 'string', Rule::in(self::SUPPORTED_LOCALES)],
            'settings_payload.timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())],
        ];
    }
}
