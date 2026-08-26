<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlatformCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlatformCategoryController extends Controller
{
    /** GET /api/v1/platform/categories — public / mobile (active only) */
    public function index(): JsonResponse
    {
        $cats = PlatformCategory::with(['children' => fn($q) => $q->active()->orderBy('order')])
            ->active()->roots()->orderBy('order')->get();
        return response()->json(['success' => true, 'message' => '', 'data' => $cats]);
    }

    /** GET /api/v1/admin/platform/categories — back-office (all, recent first) */
    public function adminIndex(): JsonResponse
    {
        $cats = PlatformCategory::with(['children' => fn($q) => $q->orderBy('updated_at', 'desc')])
            ->roots()->orderBy('updated_at', 'desc')->get();
        return response()->json(['success' => true, 'message' => '', 'data' => $cats]);
    }

    public function show(int $id): JsonResponse
    {
        $cat = PlatformCategory::with(['children', 'products'])->findOrFail($id);
        return response()->json(['success' => true, 'message' => '', 'data' => $cat]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'icon'      => 'nullable|string',
            'color'     => 'nullable|string|max:20',
            'parent_id' => 'nullable|exists:platform_categories,id',
            'is_active' => 'boolean',
            'order'     => 'integer',
        ]);
        $data['slug'] = Str::slug($data['name'] . '-' . time());
        $cat = PlatformCategory::create($data);
        return response()->json(['success' => true, 'message' => 'Catégorie créée.', 'data' => $cat], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $cat = PlatformCategory::findOrFail($id);
        $data = $request->validate([
            'name'      => 'sometimes|string|max:100',
            'icon'      => 'nullable|string',
            'color'     => 'nullable|string|max:20',
            'parent_id' => 'nullable|exists:platform_categories,id',
            'is_active' => 'boolean',
            'order'     => 'integer',
        ]);
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name'] . '-' . $id);
        }
        $cat->update($data);
        return response()->json(['success' => true, 'message' => 'Catégorie mise à jour.', 'data' => $cat]);
    }

    public function destroy(int $id): JsonResponse
    {
        PlatformCategory::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Catégorie supprimée.', 'data' => null]);
    }
}
