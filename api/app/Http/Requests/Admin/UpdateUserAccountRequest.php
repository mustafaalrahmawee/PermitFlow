<?php

namespace App\Http\Requests\Admin;

use App\Enums\AccountState;
use App\Enums\Role;
use App\Models\UserAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for maintaining an existing user account (UC-01 main flow).
 *
 * PATCH is partial: each field is `sometimes`, but when present it must be
 * well-formed. `role` stays a scalar `in:` rule, so none-or-many roles is a 422
 * [UC-01 ext 4a; BR-001]. Lifecycle blockers (deactivation / role change) are
 * evaluated in the controller against the current record, not here.
 */
class UpdateUserAccountRequest extends FormRequest
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
        /** @var UserAccount $userAccount */
        $userAccount = $this->route('userAccount');

        return [
            'display_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('user_accounts', 'email')->ignore($userAccount->id),
            ],
            'role' => ['sometimes', 'required', 'string', Rule::in(Role::values())],
            'account_state' => ['sometimes', 'required', 'string', Rule::in(AccountState::values())],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
        ];
    }
}
