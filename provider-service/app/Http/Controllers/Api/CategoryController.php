<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\ProviderCategory;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryRepositoryInterface $repo) {}

    /** GET /api/v1/categories — public / mobile (active only) */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Catégories récupérées.',
            'data'    => CategoryResource::collection($this->repo->getAllActive()),
        ]);
    }

    /** GET /api/v1/admin/categories — back-office (all, recent first) */
    public function adminIndex(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Catégories récupérées.',
            'data'    => CategoryResource::collection($this->repo->getAll()),
        ]);
    }

    /** GET /api/v1/categories/{id} */
    public function show(int $id): JsonResponse
    {
        $cat = $this->repo->findById($id);
        if (!$cat) {
            return response()->json(['success' => false, 'message' => 'Catégorie introuvable.', 'data' => null], 404);
        }
        return response()->json(['success' => true, 'message' => 'Catégorie récupérée.', 'data' => new CategoryResource($cat)]);
    }

    /** POST /api/v1/categories */
    public function store(Request $request): JsonResponse
    {
        $v = $request->validate([
            'name'        => 'required|string|max:255',
            'icon'        => 'nullable|string|max:10',
            'color'       => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string|max:1000',
            'parent_id'   => 'nullable|integer|exists:provider_categories,id',
            'order'       => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ]);
        $v['slug'] = $this->uniqueSlug($v['name']);
        $cat = $this->repo->create($v);
        return response()->json(['success' => true, 'message' => 'Catégorie créée.', 'data' => new CategoryResource($cat)], 201);
    }

    /** PUT /api/v1/categories/{id} */
    public function update(Request $request, int $id): JsonResponse
    {
        $cat = $this->repo->findById($id);
        if (!$cat) {
            return response()->json(['success' => false, 'message' => 'Catégorie introuvable.', 'data' => null], 404);
        }
        $v = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'icon'        => 'nullable|string|max:10',
            'color'       => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string|max:1000',
            'parent_id'   => 'nullable|integer|exists:provider_categories,id',
            'order'       => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ]);
        if (isset($v['name'])) $v['slug'] = $this->uniqueSlug($v['name'], $id);
        $cat = $this->repo->update($cat, $v);
        return response()->json(['success' => true, 'message' => 'Catégorie mise à jour.', 'data' => new CategoryResource($cat)]);
    }

    /** DELETE /api/v1/categories/{id} */
    public function destroy(int $id): JsonResponse
    {
        $cat = $this->repo->findById($id);
        if (!$cat) {
            return response()->json(['success' => false, 'message' => 'Catégorie introuvable.', 'data' => null], 404);
        }
        $this->repo->delete($cat);
        return response()->json(['success' => true, 'message' => 'Catégorie supprimée.', 'data' => null]);
    }

    private function uniqueSlug(string $name, ?int $excludeId = null): string
    {
        $base = Str::slug($name); $slug = $base; $i = 1;
        while (ProviderCategory::where('slug', $slug)->when($excludeId, fn($q) => $q->where('id','!=',$excludeId))->exists()) {
            $slug = "{$base}-{$i}"; $i++;
        }
        return $slug;
    }
}
