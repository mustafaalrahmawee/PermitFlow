<?php

namespace App\Http\Requests\Admin;

use App\Enums\AccountState;
use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for creating a user account (UC-01 main flow, steps 3–7).
 *
 * Authorization is enforced by the `manage-accounts` route gate, so this
 * request authorizes at the shape level only. `role` and `account_state` are
 * scalar `in:` rules against the enum value sets: a missing role, or more than
 * one role, fails validation as 422 [UC-01 ext 4a; BR-001;
 * docs/conventions.md API error responses — 422].
 */
class StoreUserAccountRequest extends FormRequest
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
            'display_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('user_accounts', 'email')],
            'role' => ['required', 'string', Rule::in(Role::values())],
            'account_state' => ['required', 'string', Rule::in(AccountState::values())],
            // Implementation-only column for local login (docs/conventions.md).
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
