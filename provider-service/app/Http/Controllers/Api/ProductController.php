<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\ProviderProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request, int $providerId): JsonResponse
    {
        $q = ProviderProduct::with('category')->where('provider_id', $providerId);
        if ($request->has('is_active')) {
            $q->where('is_active', $request->boolean('is_active'));
        }
        $products = $q->latest()->paginate($request->input('per_page', 20));
        return response()->json(['success' => true, 'message' => '', 'data' => [
            'products'     => $products->items(),
            'total'        => $products->total(),
            'current_page' => $products->currentPage(),
            'last_page'    => $products->lastPage(),
        ]]);
    }

    public function store(Request $request, int $providerId): JsonResponse
    {
        Provider::findOrFail($providerId);
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'description'         => 'nullable|string',
            'product_category_id' => 'nullable|exists:product_categories,id',
            'price'               => 'required|numeric|min:0',
            'price_promo'         => 'nullable|numeric|min:0',
            'stock'               => 'integer|min:-1',
            'image'               => 'nullable|string',
            'is_active'           => 'boolean',
            'is_featured'         => 'boolean',
            'variants'            => 'nullable|array',
        ]);
        $data['slug']        = Str::slug($data['name'] . '-' . time());
        $data['provider_id'] = $providerId;
        return response()->json(['success' => true, 'message' => 'Produit créé.', 'data' => ProviderProduct::create($data)], 201);
    }

    public function update(Request $request, int $providerId, int $id): JsonResponse
    {
        $product = ProviderProduct::where('provider_id', $providerId)->findOrFail($id);
        $data = $request->validate([
            'name'                => 'sometimes|string|max:255',
            'description'         => 'nullable|string',
            'product_category_id' => 'nullable|exists:product_categories,id',
            'price'               => 'sometimes|numeric|min:0',
            'price_promo'         => 'nullable|numeric|min:0',
            'stock'               => 'integer|min:-1',
            'image'               => 'nullable|string',
            'is_active'           => 'boolean',
            'is_featured'         => 'boolean',
            'variants'            => 'nullable|array',
        ]);
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name'] . '-' . $id);
        }
        $product->update($data);
        return response()->json(['success' => true, 'message' => 'Produit mis à jour.', 'data' => $product->fresh()]);
    }

    public function destroy(int $providerId, int $id): JsonResponse
    {
        ProviderProduct::where('provider_id', $providerId)->findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Produit supprimé.', 'data' => null]);
    }
}
