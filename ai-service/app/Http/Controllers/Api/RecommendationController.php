<?php

namespace App\Http\Controllers\Api;

use App\AI\Agents\RecommendationAgent;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function __construct(
        private readonly RecommendationAgent $agent,
    ) {}

    /**
     * GET /api/ai/recommendations/{user_id}
     *
     * Query: lat?, lng?, radius?
     */
    public function recommend(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'lat'    => 'nullable|numeric|between:-90,90',
            'lng'    => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:100',
        ]);

        $location = array_filter([
            'lat'    => $validated['lat']    ?? null,
            'lng'    => $validated['lng']    ?? null,
            'radius' => $validated['radius'] ?? null,
        ], fn($v) => !is_null($v));

        $result = $this->agent->recommend($userId, $location);

        return response()->json([
            'success' => true,
            'message' => 'Recommandations générées.',
            'data'    => $result,
        ]);
    }
}
