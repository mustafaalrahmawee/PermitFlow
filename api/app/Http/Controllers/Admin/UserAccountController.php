<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountState;
use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserAccountRequest;
use App\Http\Requests\Admin\UpdateUserAccountRequest;
use App\Models\UserAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Administrator maintenance of user accounts and roles (UC-01).
 *
 * Every action is administrator-only: the route group applies `auth:sanctum`
 * and the `manage-accounts` gate, which fails closed for inactive or non-admin
 * actors (403) [BR-013; docs/conventions.md Authorization]. Missing accounts
 * resolve to 404 via implicit route-model binding (ext 2a). Lifecycle rules
 * that protect request history (ext 5a/5b/5c) return 409, and writes are
 * wrapped in a transaction so a failed save leaves accounts unchanged
 * (ext 7a) [docs/conventions.md API error responses].
 */
class UserAccountController extends Controller
{
    /**
     * Fixed page size for the account list; secondary/performance concerns are a
     * v1 non-goal, so this is a constant rather than a client-tunable parameter.
     */
    private const PER_PAGE = 15;

    /**
     * List accounts with their role and account state (steps 1–3), paginated.
     *
     * The list is paginated at a fixed page size; `paginate()` reads the 1-based
     * page from the `page` query parameter. `data` stays the array of accounts —
     * the raw paginator is never returned (no nested `data.data`) — and the page
     * cursor travels alongside in `meta` [docs/conventions.md API success responses].
     */
    public function index(): JsonResponse
    {
        $accounts = UserAccount::query()
            ->orderBy('display_name')
            ->paginate(self::PER_PAGE);

        return response()->json([
            'data' => $accounts->items(),
            'meta' => [
                'current_page' => $accounts->currentPage(),
                'last_page' => $accounts->lastPage(),
                'per_page' => $accounts->perPage(),
                'total' => $accounts->total(),
            ],
            'message' => 'User accounts retrieved.',
        ]);
    }

    /**
     * Return a single account for maintenance (steps 2–3). A missing account is
     * a 404 through route-model binding, leaving existing accounts unchanged
     * (ext 2a).
     */
    public function show(UserAccount $userAccount): JsonResponse
    {
        return response()->json([
            'data' => $userAccount,
            'message' => 'User account retrieved.',
        ]);
    }

    /**
     * Create a new account with exactly one role and an account state (steps 2–8).
     */
    public function store(StoreUserAccountRequest $request): JsonResponse
    {
        $account = DB::transaction(
            fn () => UserAccount::create($request->validated())
        );

        return response()->json([
            'data' => $account,
            'message' => 'User account created.',
        ], 201);
    }

    /**
     * Maintain an existing account, enforcing the lifecycle blockers before the
     * write so existing accounts, roles, and states are unchanged on rejection.
     */
    public function update(UpdateUserAccountRequest $request, UserAccount $userAccount): JsonResponse
    {
        $data = $request->validated();

        $this->guardDeactivation($data, $userAccount);
        $this->guardRoleChange($data, $userAccount);

        DB::transaction(function () use ($userAccount, $data): void {
            $userAccount->update($data);
        });

        return response()->json([
            'data' => $userAccount->fresh(),
            'message' => 'User account updated.',
        ]);
    }

    /**
     * ext 5a/5b — deactivating an account tied to an undecided request is blocked
     * until the request is decided. An undecided request is any request not in
     * the terminal Decided status, owned by the account (citizen, 5a) or under
     * its responsibility (staff, 5b) [UC-01 ext 5a/5b; 409].
     *
     * @param  array<string, mixed>  $data
     */
    private function guardDeactivation(array $data, UserAccount $userAccount): void
    {
        $isDeactivating = array_key_exists('account_state', $data)
            && $data['account_state'] === AccountState::Inactive->value
            && $userAccount->isActive();

        if (! $isDeactivating) {
            return;
        }

        $ownsUndecided = $userAccount->ownedRequests()
            ->where('status', '!=', RequestStatus::Decided->value)
            ->exists();

        $responsibleForUndecided = $userAccount->assignedRequests()
            ->where('status', '!=', RequestStatus::Decided->value)
            ->exists();

        abort_if(
            $ownsUndecided || $responsibleForUndecided,
            409,
            'Cannot deactivate an account tied to an undecided request until it is decided.'
        );
    }

    /**
     * ext 5c — changing the role of an account connected to request ownership,
     * responsibility, messages, or decisions is blocked in v1 to protect request
     * history and responsibility [UC-01 ext 5c; BR-017; 409].
     *
     * @param  array<string, mixed>  $data
     */
    private function guardRoleChange(array $data, UserAccount $userAccount): void
    {
        $isChangingRole = array_key_exists('role', $data)
            && $data['role'] !== $userAccount->role->value;

        if (! $isChangingRole) {
            return;
        }

        $connectedToHistory = $userAccount->ownedRequests()->exists()
            || $userAccount->assignedRequests()->exists()
            || $userAccount->sentMessages()->exists()
            || $userAccount->receivedMessages()->exists()
            || $userAccount->decisionsMade()->exists();

        abort_if(
            $connectedToHistory,
            409,
            'Cannot change the role of an account connected to request history.'
        );
    }
}
