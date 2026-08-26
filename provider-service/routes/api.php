<?php
use App\Http\Controllers\Api\AdminProviderController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PlatformCategoryController;
use App\Http\Controllers\Api\PlatformProductController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProviderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ── Catégories prestataires (public) ─────────────────────────────────────
    Route::get('categories',      [CategoryController::class, 'index']);
    Route::get('categories/{id}', [CategoryController::class, 'show']);

    // ── Prestataires (public) ─────────────────────────────────────────────────
    Route::get('providers/nearby',                              [ProviderController::class, 'nearby']);
    Route::get('providers',                                     [ProviderController::class, 'index']);
    Route::get('providers/{id}',                               [ProviderController::class, 'show']);
    Route::get('providers/{id}/services',                      [ProviderController::class, 'services']);
    Route::get('providers/{id}/reviews',                       [ProviderController::class, 'reviews']);
    Route::get('providers/{id}/schedule',                      [ProviderController::class, 'schedule']);
    Route::get('providers/{providerId}/availability',           [AvailabilityController::class, 'check']);
    Route::get('availability/{providerId}/{date}',              [AvailabilityController::class, 'slots']);
    Route::get('providers/{providerId}/services/{serviceId}',  [ProviderController::class, 'serviceById']);

    // ── Produits prestataires (public) ────────────────────────────────────────
    Route::get('providers/{providerId}/products',              [ProductController::class, 'index']);

    // ── Catalogue plateforme (public) ─────────────────────────────────────────
    Route::get('platform/categories',      [PlatformCategoryController::class, 'index']);
    Route::get('platform/categories/{id}', [PlatformCategoryController::class, 'show']);
    Route::get('platform/products',        [PlatformProductController::class, 'index']);
    Route::get('platform/products/{id}',   [PlatformProductController::class, 'show']);

    // ── Routes protégées ─────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Prestataires — gestion
        Route::post('providers',                              [ProviderController::class, 'store']);
        Route::put('providers/{id}',                         [ProviderController::class, 'update']);
        Route::delete('providers/{id}',                      [ProviderController::class, 'destroy']);
        Route::put('providers/{id}/schedule',                [ProviderController::class, 'updateSchedule']);
        Route::post('providers/{id}/services',               [ProviderController::class, 'storeService']);
        Route::put('providers/{id}/services/{serviceId}',    [ProviderController::class, 'updateService']);
        Route::delete('providers/{id}/services/{serviceId}', [ProviderController::class, 'destroyService']);
        Route::post('providers/{id}/reviews',                [ProviderController::class, 'addReview']);

        // Catégories prestataires — gestion (back-office)
        Route::get('admin/categories',       [CategoryController::class, 'adminIndex']);
        Route::post('categories',         [CategoryController::class, 'store']);
        Route::put('categories/{id}',     [CategoryController::class, 'update']);
        Route::delete('categories/{id}',  [CategoryController::class, 'destroy']);

        // Produits prestataire — gestion par le prestataire
        Route::post('providers/{providerId}/products',         [ProductController::class, 'store']);
        Route::put('providers/{providerId}/products/{id}',     [ProductController::class, 'update']);
        Route::delete('providers/{providerId}/products/{id}',  [ProductController::class, 'destroy']);

        // Admin — catalogue plateforme
        Route::get('admin/platform/categories',    [PlatformCategoryController::class, 'adminIndex']);
        Route::post('platform/categories',         [PlatformCategoryController::class, 'store']);
        Route::put('platform/categories/{id}',     [PlatformCategoryController::class, 'update']);
        Route::delete('platform/categories/{id}',  [PlatformCategoryController::class, 'destroy']);
        Route::post('platform/products',           [PlatformProductController::class, 'store']);
        Route::put('platform/products/{id}',       [PlatformProductController::class, 'update']);
        Route::delete('platform/products/{id}',    [PlatformProductController::class, 'destroy']);

        // Admin — validation prestataires
        Route::get('admin/providers/pending',             [AdminProviderController::class, 'pending']);
        Route::post('admin/providers/{id}/validate',      [AdminProviderController::class, 'validate']);
        Route::post('admin/providers/{id}/reject',        [AdminProviderController::class, 'reject']);
        Route::put('admin/providers/{id}/commission',     [AdminProviderController::class, 'updateCommission']);
    });
});
