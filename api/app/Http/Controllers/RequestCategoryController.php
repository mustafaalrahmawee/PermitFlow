<?php

namespace App\Http\Controllers;

use App\Models\RequestCategory;
use Illuminate\Http\JsonResponse;

/**
 * Citizen-facing read of request categories for filing a request (UC-02 step 3).
 *
 * Only active categories are selectable, so the list is filtered to
 * `is_active = true`; with none available the list is empty and the citizen
 * cannot select a category (ext 3c). This is a selection list, not the
 * administrator maintenance seam (that lives under `admin/`), so it is a flat
 * `data` array with no pagination [03_use-cases.md UC-02 ext 3c;
 * 02_business-rules.md BR-002; docs/conventions.md API success responses].
 */
class RequestCategoryController extends Controller
{
    /**
     * List the active categories a citizen may file a request under.
     */
    public function index(): JsonResponse
    {
        $categories = RequestCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $categories,
            'message' => 'Active request categories retrieved.',
        ]);
    }
}
