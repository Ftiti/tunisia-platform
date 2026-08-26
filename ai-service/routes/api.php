<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\ClassificationController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('ai')->group(function () {
    Route::get('health', fn () => response()->json([
        'success' => true,
        'service' => 'ai-service',
        'status'  => 'ok',
        'version' => '1.1.0',
    ]));

    Route::post('search',               [SearchController::class, 'search']);
    Route::post('chat',                 [ChatbotController::class, 'message']);
    Route::delete('chat/{session_id}',  [ChatbotController::class, 'clear']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('recommendations/{user_id}',  [RecommendationController::class, 'recommend']);
        Route::post('classify',                  [ClassificationController::class, 'classify']);
        Route::get('analytics/daily',            [AnalyticsController::class, 'daily']);
        Route::get('analytics/weekly',           [AnalyticsController::class, 'weekly']);
    });
});
