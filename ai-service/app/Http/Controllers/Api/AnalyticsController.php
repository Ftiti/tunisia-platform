<?php

namespace App\Http\Controllers\Api;

use App\AI\Agents\AnalyticsAgent;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsAgent $agent) {}

    public function daily(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => $this->agent->dailySummary(),
        ]);
    }

    public function weekly(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => $this->agent->weeklyStats(),
        ]);
    }
}
