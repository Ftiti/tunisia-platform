<?php

namespace App\Http\Controllers\Admin;

use App\Events\ProviderRejected;
use App\Events\ProviderValidated;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderValidationController extends Controller
{
    public function pending(Request $request): JsonResponse
    {
        $providers = User::where('role', 'provider')
            ->where('validation_status', 'pending')
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => [
                'providers'    => UserResource::collection($providers),
                'total'        => $providers->total(),
                'per_page'     => $providers->perPage(),
                'current_page' => $providers->currentPage(),
                'last_page'    => $providers->lastPage(),
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = User::where('role', 'provider');

        if ($request->has('validation_status')) {
            $query->where('validation_status', $request->validation_status);
        }

        $providers = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => [
                'providers'    => UserResource::collection($providers),
                'total'        => $providers->total(),
                'per_page'     => $providers->perPage(),
                'current_page' => $providers->currentPage(),
                'last_page'    => $providers->lastPage(),
            ],
        ]);
    }

    public function validate(User $user): JsonResponse
    {
        if ($user->role !== 'provider') {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non prestataire.',
            ], 422);
        }

        $user->update([
            'validation_status' => 'validated',
            'validated_at'      => now(),
        ]);

        event(new ProviderValidated($user));

        return response()->json([
            'success' => true,
            'message' => 'Prestataire validé.',
            'data'    => new UserResource($user),
        ]);
    }

    public function reject(Request $request, User $user): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        if ($user->role !== 'provider') {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non prestataire.',
            ], 422);
        }

        $user->update([
            'validation_status' => 'rejected',
            'rejection_reason'  => $request->reason,
        ]);

        event(new ProviderRejected($user, $request->reason));

        return response()->json([
            'success' => true,
            'message' => 'Prestataire rejeté.',
            'data'    => new UserResource($user),
        ]);
    }

    public function suspend(User $user): JsonResponse
    {
        $user->update(['validation_status' => 'suspended']);

        return response()->json([
            'success' => true,
            'message' => 'Prestataire suspendu.',
            'data'    => new UserResource($user),
        ]);
    }
}
