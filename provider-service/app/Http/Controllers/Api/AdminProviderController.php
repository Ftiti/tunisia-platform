<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProviderController extends Controller
{
    public function __construct(private readonly ProviderRepositoryInterface $repo) {}

    public function pending(Request $request): JsonResponse
    {
        $providers = Provider::with('category')
            ->where('validation_status', 'pending')
            ->latest()
            ->paginate(20);
        return response()->json(['success' => true, 'message' => '', 'data' => [
            'providers'    => $providers->items(),
            'total'        => $providers->total(),
            'current_page' => $providers->currentPage(),
            'last_page'    => $providers->lastPage(),
        ]]);
    }

    public function validate(int $id): JsonResponse
    {
        $p = Provider::findOrFail($id);
        $p->update(['validation_status' => 'validated', 'is_verified' => true]);
        return response()->json(['success' => true, 'message' => 'Prestataire validé.', 'data' => $p]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $p = Provider::findOrFail($id);
        $p->update(['validation_status' => 'rejected']);
        return response()->json(['success' => true, 'message' => 'Prestataire rejeté.', 'data' => $p]);
    }

    public function updateCommission(Request $request, int $id): JsonResponse
    {
        $request->validate(['commission_rate' => 'required|numeric|min:0|max:100']);
        $p = Provider::findOrFail($id);
        $p->update(['commission_rate' => $request->commission_rate]);
        return response()->json(['success' => true, 'message' => 'Commission mise à jour.', 'data' => $p]);
    }
}
