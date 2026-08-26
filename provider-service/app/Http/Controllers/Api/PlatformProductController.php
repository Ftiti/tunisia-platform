<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlatformProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlatformProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = PlatformProduct::with('category')->active();
        if ($request->has('category_id')) {
            $q->where('platform_category_id', $request->category_id);
        }
        if ($request->has('is_promo')) {
            $q->where('is_promo', true);
        }
        if ($request->has('is_vedette')) {
            $q->where('is_vedette', true);
        }
        if ($request->keyword) {
            $q->where('name', 'ilike', '%' . $request->keyword . '%');
        }
        $products = $q->latest()->paginate($request->input('per_page', 20));
        return response()->json(['success' => true, 'message' => '', 'data' => [
            'products'     => $products->items(),
            'total'        => $products->total(),
            'current_page' => $products->currentPage(),
            'last_page'    => $products->lastPage(),
        ]]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => PlatformProduct::with('category')->findOrFail($id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                 => 'required|string|max:255',
            'description'          => 'nullable|string',
            'platform_category_id' => 'nullable|exists:platform_categories,id',
            'price'                => 'required|numeric|min:0',
            'price_promo'          => 'nullable|numeric|min:0',
            'stock'                => 'integer|min:-1',
            'image'                => 'nullable|string',
            'is_active'            => 'boolean',
            'is_featured'          => 'boolean',
            'is_promo'             => 'boolean',
            'is_vedette'           => 'boolean',
            'delivery_type'        => 'in:national,local',
            'variants'             => 'nullable|array',
        ]);
        $data['slug'] = Str::slug($data['name'] . '-' . time());
        return response()->json(['success' => true, 'message' => 'Produit créé.', 'data' => PlatformProduct::create($data)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = PlatformProduct::findOrFail($id);
        $data = $request->validate([
            'name'                 => 'sometimes|string|max:255',
            'description'          => 'nullable|string',
            'platform_category_id' => 'nullable|exists:platform_categories,id',
            'price'                => 'sometimes|numeric|min:0',
            'price_promo'          => 'nullable|numeric|min:0',
            'stock'                => 'integer|min:-1',
            'image'                => 'nullable|string',
            'is_active'            => 'boolean',
            'is_featured'          => 'boolean',
            'is_promo'             => 'boolean',
            'is_vedette'           => 'boolean',
            'delivery_type'        => 'in:national,local',
            'variants'             => 'nullable|array',
        ]);
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name'] . '-' . $id);
        }
        $product->update($data);
        return response()->json(['success' => true, 'message' => 'Produit mis à jour.', 'data' => $product->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        PlatformProduct::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Produit supprimé.', 'data' => null]);
    }
}
