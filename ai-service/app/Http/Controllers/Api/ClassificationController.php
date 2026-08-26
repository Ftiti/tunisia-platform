<?php

namespace App\Http\Controllers\Api;

use App\AI\Agents\ClassificationAgent;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassificationController extends Controller
{
    public function __construct(
        private readonly ClassificationAgent $agent,
    ) {}

    /**
     * POST /api/ai/classify
     *
     * Body: { provider_id?, name, description }
     */
    public function classify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider_id' => 'nullable|integer',
            'name'        => 'required|string|min:2|max:255',
            'description' => 'nullable|string|max:2000',
        ]);

        $result = $this->agent->classify($validated);

        return response()->json([
            'success' => true,
            'message' => 'Classification effectuée.',
            'data'    => $result,
        ]);
    }
}
