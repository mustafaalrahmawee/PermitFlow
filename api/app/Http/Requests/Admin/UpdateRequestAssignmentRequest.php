<?php

namespace App\Http\Requests\Admin;

use App\Enums\AccountState;
use App\Enums\Role;
use App\Models\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for assigning or reassigning a request (UC-05 steps 3–4).
 *
 * Authorization is enforced by the `assign-requests` route gate, so this request
 * authorizes at the shape level only. The assignee is a single id (a scalar
 * `integer`, so an array or missing value fails as 422 — ext 3a) that must
 * resolve to an active Staff-member account (the scoped `exists` rule fails an
 * inactive or non-staff account as 422 — ext 3b). On a reassignment the assignee
 * must also differ from the current responsible staff member — reassigning to the
 * same person is a no-op change and is rejected as 422. `reason` is required only
 * when the request is already assigned, i.e. this is a reassignment (ext 4a)
 * [03_use-cases.md UC-05 ext 3a/3b/4a; BR-001; BR-009; docs/conventions.md API
 * error responses — 422].
 */
class UpdateRequestAssignmentRequest extends FormRequest
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
        $currentStaffId = $this->currentStaffId();

        return [
            'responsible_staff_user_account_id' => [
                'required',
                'integer',
                Rule::exists('user_accounts', 'id')
                    ->where('role', Role::StaffMember->value)
                    ->where('account_state', AccountState::Active->value),
                // On reassignment the new assignee must differ from the current
                // responsible staff member; picking the same person is a no-op.
                ...($currentStaffId !== null ? [Rule::notIn([$currentStaffId])] : []),
            ],
            // Required only on reassignment (the request already has a
            // responsible staff member); optional on first assignment.
            'reason' => ['nullable', 'string', 'max:2000', Rule::requiredIf($this->isReassignment())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'responsible_staff_user_account_id.not_in' =>
                'This request is already assigned to that staff member; choose a different one.',
        ];
    }

    /**
     * Whether the bound request already has a responsible staff member, making
     * this a reassignment rather than a first assignment.
     */
    private function isReassignment(): bool
    {
        return $this->currentStaffId() !== null;
    }

    /**
     * The current responsible staff id of the bound request, or null when the
     * request is unassigned (a first assignment) or not bound.
     */
    private function currentStaffId(): ?int
    {
        $request = $this->route('request');

        return $request instanceof Request
            ? $request->responsible_staff_user_account_id
            : null;
    }
}
