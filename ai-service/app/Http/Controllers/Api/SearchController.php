<?php

namespace App\Http\Controllers\Api;

use App\AI\Agents\SearchAgent;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private readonly SearchAgent $agent,
    ) {}

    /**
     * POST /api/ai/search
     *
     * Body: { query, lat?, lng?, radius? }
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query'  => 'required|string|min:2|max:500',
            'lat'    => 'nullable|numeric|between:-90,90',
            'lng'    => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:100',
        ]);

        $location = array_filter([
            'lat'    => $validated['lat']    ?? null,
            'lng'    => $validated['lng']    ?? null,
            'radius' => $validated['radius'] ?? null,
        ], fn($v) => !is_null($v));

        $result = $this->agent->search($validated['query'], $location);

        return response()->json([
            'success' => true,
            'message' => 'Recherche effectuée.',
            'data'    => $result,
        ]);
    }
}
