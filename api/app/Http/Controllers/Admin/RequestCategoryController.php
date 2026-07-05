<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRequestCategoryRequest;
use App\Http\Requests\Admin\UpdateRequestCategoryRequest;
use App\Models\RequestCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Administrator maintenance of request categories (UC-11).
 *
 * Every action is administrator-only: the route group applies `auth:sanctum`
 * and the `manage-categories` gate, which fails closed for inactive or non-admin
 * actors (403) [BR-012; docs/conventions.md Authorization]. A missing category
 * resolves to 404 via implicit route-model binding (ext 2a). Used-category rules
 * that protect existing request understandability return 409 — a semantic rename
 * (ext 6b) and a delete (ext 6a) are blocked while the category is referenced by
 * existing requests; deactivating a used category is allowed (ext 6c). Writes are
 * wrapped in a transaction so a failed save leaves categories unchanged
 * (ext 7a) [docs/conventions.md API error responses].
 */
class RequestCategoryController extends Controller
{
    /**
     * Fixed page size for the category list; secondary/performance concerns are a
     * v1 non-goal, so this is a constant rather than a client-tunable parameter.
     */
    private const PER_PAGE = 15;

    /**
     * List categories with their name, description, and availability flag
     * (steps 1–2), paginated. `data` stays the flat array of categories and the
     * page cursor rides alongside in `meta` [docs/conventions.md API success
     * responses].
     */
    public function index(): JsonResponse
    {
        $categories = RequestCategory::query()
            ->orderBy('name')
            ->paginate(self::PER_PAGE);

        return response()->json([
            'data' => $categories->items(),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
            'message' => 'Request categories retrieved.',
        ]);
    }

    /**
     * Return a single category for maintenance (steps 2–3). A missing category is
     * a 404 through route-model binding, leaving existing categories unchanged
     * (ext 2a).
     */
    public function show(RequestCategory $requestCategory): JsonResponse
    {
        return response()->json([
            'data' => $requestCategory,
            'message' => 'Request category retrieved.',
        ]);
    }

    /**
     * Create a new category with a unique name and an availability flag
     * (steps 2–8).
     */
    public function store(StoreRequestCategoryRequest $request): JsonResponse
    {
        $category = DB::transaction(
            fn () => RequestCategory::create($request->validated())
        );

        return response()->json([
            'data' => $category,
            'message' => 'Request category created.',
        ], 201);
    }

    /**
     * Maintain an existing category, enforcing the used-category blocker before
     * the write so existing request categories are unchanged on rejection. A name
     * change to a category already used by existing requests is blocked (ext 6b);
     * description and availability changes — including deactivation (ext 6c) — are
     * always allowed.
     */
    public function update(UpdateRequestCategoryRequest $request, RequestCategory $requestCategory): JsonResponse
    {
        $data = $request->validated();

        $this->guardSemanticRename($data, $requestCategory);

        DB::transaction(function () use ($requestCategory, $data): void {
            $requestCategory->update($data);
        });

        return response()->json([
            'data' => $requestCategory->fresh(),
            'message' => 'Request category updated.',
        ]);
    }

    /**
     * Delete a category only when no existing request uses it (ext 6a). A used
     * category is retained and every referencing request is left untouched; the
     * restrict-on-delete FK is the structural backstop, but the check returns a
     * clean 409 instead of a persistence error [UC-11 ext 6a; 409].
     */
    public function destroy(RequestCategory $requestCategory): JsonResponse
    {
        abort_if(
            $requestCategory->requests()->exists(),
            409,
            'Cannot delete a category that is already used by existing requests.'
        );

        DB::transaction(function () use ($requestCategory): void {
            $requestCategory->delete();
        });

        return response()->json([
            'data' => null,
            'message' => 'Request category deleted.',
        ]);
    }

    /**
     * ext 6b — renaming a category already used by existing requests is blocked,
     * because existing request history would become unclear. In v1 any change to
     * the `name` of a used category is treated as a semantic rename and rejected;
     * deactivation and description edits remain available [UC-11 ext 6b; BR-017;
     * 409].
     *
     * @param  array<string, mixed>  $data
     */
    private function guardSemanticRename(array $data, RequestCategory $requestCategory): void
    {
        $isRenaming = array_key_exists('name', $data)
            && $data['name'] !== $requestCategory->name;

        if (! $isRenaming) {
            return;
        }

        abort_if(
            $requestCategory->requests()->exists(),
            409,
            'Cannot rename a category that is already used by existing requests.'
        );
    }
}
