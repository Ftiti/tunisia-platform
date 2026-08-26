<?php

namespace App\AI\Agents;

use App\Models\AiSearchLog;
use App\Models\AiConversation;

class AnalyticsAgent
{
    public function dailySummary(): array
    {
        $today = now()->startOfDay();

        $searches      = AiSearchLog::where('created_at', '>=', $today)->get();
        $conversations = AiConversation::where('updated_at', '>=', $today)->count();

        $avgResponseTime = $searches->avg('response_time_ms') ?? 0;

        $topCategories = $searches->pluck('parsed_filters')
            ->map(fn($f) => $f['category'] ?? null)
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->toArray();

        $topCities = $searches->pluck('parsed_filters')
            ->map(fn($f) => $f['city'] ?? null)
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->toArray();

        return [
            'date'                   => now()->toDateString(),
            'total_searches'         => $searches->count(),
            'total_conversations'    => $conversations,
            'avg_response_time_ms'   => round($avgResponseTime),
            'top_categories'         => $topCategories,
            'top_cities'             => $topCities,
            'zero_result_searches'   => $searches->where('results_count', 0)->count(),
            'avg_results_per_search' => round($searches->avg('results_count') ?? 0, 1),
        ];
    }

    public function weeklyStats(): array
    {
        $from = now()->subDays(7)->startOfDay();

        $searches = AiSearchLog::where('created_at', '>=', $from)
            ->selectRaw("DATE(created_at) as date, COUNT(*) as count, AVG(response_time_ms) as avg_time, AVG(results_count) as avg_results")
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        return [
            'from'        => $from->toDateString(),
            'to'          => now()->toDateString(),
            'daily_stats' => $searches->toArray(),
            'total'       => $searches->sum('count'),
        ];
    }
}
