<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ProviderValidationController;
use App\Http\Controllers\Profile\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes API — Auth Service
|--------------------------------------------------------------------------
|
| Préfixe global: /api  (défini dans bootstrap/app.php)
| Structure réponse: { success, message, data }
|
*/

// ── Routes publiques d'authentification ──────────────────────────────────────
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/register',          [AuthController::class, 'register'])->name('register');
    Route::post('/register/client',   [AuthController::class, 'registerClient'])->name('register.client');
    Route::post('/register/provider', [AuthController::class, 'registerProvider'])->name('register.provider');
    Route::post('/login',             [AuthController::class, 'login'])->name('login');
});

// ── Routes protégées par Sanctum ─────────────────────────────────────────────
Route::prefix('auth')->name('auth.')->middleware('auth:sanctum')->group(function () {
    Route::post('/logout',        [AuthController::class, 'logout'])->name('logout');
    Route::get('/me',             [AuthController::class, 'me'])->name('me');
    Route::post('/refresh-token', [AuthController::class, 'refreshToken'])->name('refresh-token');
});

// ── Routes Profil — protégées par Sanctum ────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
});

// ── Routes Admin — protégées par auth:sanctum + rôle admin ───────────────────
Route::prefix('admin')->name('admin.')
    ->middleware(['auth:sanctum', 'role:super_admin,admin,manager,moderator'])
    ->group(function () {

        // Lecture: accessible à tous les rôles admin
        Route::get('/permissions',  [AdminController::class, 'permissions'])->name('permissions');
        Route::get('/users',        [AdminController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [AdminController::class, 'show'])->name('users.show');

        // Écriture: réservée aux super_admin et admin
        Route::middleware('role:super_admin,admin')->group(function () {
            Route::post('/users',          [AdminController::class, 'store'])->name('users.store');
            Route::delete('/users/{user}', [AdminController::class, 'destroy'])->name('users.destroy');
        });

        // Modification: super_admin, admin et manager
        Route::middleware('role:super_admin,admin,manager')->group(function () {
            Route::put('/users/{user}', [AdminController::class, 'update'])->name('users.update');
        });

        // Validation des prestataires
        Route::get('/providers/pending',          [ProviderValidationController::class, 'pending']);
        Route::get('/providers',                  [ProviderValidationController::class, 'index']);
        Route::post('/providers/{user}/validate', [ProviderValidationController::class, 'validate']);
        Route::post('/providers/{user}/reject',   [ProviderValidationController::class, 'reject']);
        Route::post('/providers/{user}/suspend',  [ProviderValidationController::class, 'suspend']);
    });
