<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\AvailabilityController;
use Illuminate\Support\Facades\Route;

// ── Routes publiques ──────────────────────────────────────────────────────────
Route::get('availability/{provider_id}/{date}', [AvailabilityController::class, 'slots']);
Route::get('availability/{provider_id}/next',   [AvailabilityController::class, 'next']);

// ── Routes client ─────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:client,admin,super_admin'])->group(function () {
    Route::get('bookings',         [BookingController::class, 'index']);
    Route::post('bookings',        [BookingController::class, 'store']);
    Route::get('bookings/{id}',    [BookingController::class, 'show']);
    Route::put('bookings/{id}/cancel', [BookingController::class, 'cancel']);
});

// ── Routes prestataire ────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:provider,admin,super_admin'])->group(function () {
    Route::put('bookings/{id}/confirm',  [BookingController::class, 'confirm']);
    Route::put('bookings/{id}/complete', [BookingController::class, 'complete']);
});

// ── Routes admin ──────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:admin,super_admin,manager,moderator'])->group(function () {
    Route::get('admin/bookings', [BookingController::class, 'adminIndex']);
});
