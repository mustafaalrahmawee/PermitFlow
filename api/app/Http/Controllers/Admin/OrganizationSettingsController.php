<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrganizationSettingsRequest;
use App\Models\OrganizationSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Administrator maintenance of the single organization-settings record (UC-12).
 *
 * Both actions are administrator-only: the route group applies `auth:sanctum`
 * and the `manage-settings` gate, which fails closed for inactive or non-admin
 * actors (403) [BR-014; docs/conventions.md Authorization]. The record is a
 * singleton keyed by `single_organization`, seeded once and never created or
 * deleted in v1, so there is no id parameter and no store/destroy action. The
 * write is wrapped in a transaction and `settings_payload` is replaced as one
 * whole block, so a rejected or failed save leaves the previous settings active
 * (ext 5a) [docs/conventions.md Data & schema — whole-block JSON].
 */
class OrganizationSettingsController extends Controller
{
    /**
     * The fixed v1 singleton key; the single organization-settings row is
     * addressed by this value, not by id [04_data-model.md §2.1].
     */
    private const SINGLETON_KEY = 'single_organization';

    /**
     * Return the current organization settings for maintenance (steps 1–2). The
     * record exists from seeding, so this always resolves the singleton row.
     */
    public function show(): JsonResponse
    {
        return response()->json([
            'data' => $this->singleton(),
            'message' => 'Organization settings retrieved.',
        ]);
    }

    /**
     * Save changed organization settings and make them effective (steps 3–6).
     * The acting administrator is recorded as the last updater; the whole
     * `settings_payload` block is written at once. On validation failure the
     * request never reaches here and the previous settings remain active
     * (ext 5a).
     */
    public function update(UpdateOrganizationSettingsRequest $request): JsonResponse
    {
        $settings = $this->singleton();

        DB::transaction(function () use ($settings, $request): void {
            $settings->update([
                ...$request->validated(),
                'updated_by_user_account_id' => $request->user()->id,
            ]);
        });

        return response()->json([
            'data' => $settings->fresh(),
            'message' => 'Organization settings updated.',
        ]);
    }

    /**
     * Resolve the single organization-settings row by its fixed singleton key.
     */
    private function singleton(): OrganizationSettings
    {
        return OrganizationSettings::query()
            ->where('singleton_key', self::SINGLETON_KEY)
            ->firstOrFail();
    }
}
