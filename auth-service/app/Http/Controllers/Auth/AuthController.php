<?php

namespace App\Http\Controllers\Auth;

use App\Events\UserLoggedIn;
use App\Events\UserRegistered;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterClientRequest;
use App\Http\Requests\Auth\RegisterProviderRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * Inscription d'un nouvel utilisateur.
     * POST /api/auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->userRepository->create($request->validated());

        $token = $user->createToken('auth_token')->plainTextToken;

        event(new UserRegistered($user));

        return response()->json([
            'success' => true,
            'message' => 'Compte créé avec succès.',
            'data'    => [
                'user'       => new UserResource($user),
                'token'      => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    /**
     * Connexion d'un utilisateur existant.
     * POST /api/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects.',
                'data'    => null,
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            return response()->json([
                'success' => false,
                'message' => 'Compte désactivé. Veuillez contacter l\'administrateur.',
                'data'    => null,
            ], 403);
        }

        if ($user->role === 'provider') {
            if ($user->validation_status === 'pending') {
                Auth::logout();
                return response()->json(['success' => false, 'message' => 'Compte prestataire en attente de validation.', 'data' => null], 403);
            }
            if ($user->validation_status === 'rejected') {
                Auth::logout();
                return response()->json(['success' => false, 'message' => 'Compte prestataire rejeté. Raison: ' . ($user->rejection_reason ?? 'Non spécifiée') . '.', 'data' => null], 403);
            }
            if ($user->validation_status === 'suspended') {
                Auth::logout();
                return response()->json(['success' => false, 'message' => 'Compte prestataire suspendu.', 'data' => null], 403);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        event(new UserLoggedIn($user, $request->ip()));

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie.',
            'data'    => [
                'user'       => new UserResource($user),
                'token'      => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Déconnexion — révocation du token courant.
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie.',
            'data'    => null,
        ]);
    }

    /**
     * Retourne le profil de l'utilisateur authentifié.
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Profil récupéré avec succès.',
            'data'    => new UserResource($request->user()),
        ]);
    }

    /**
     * Renouvelle le token d'accès (révoque l'ancien, émet un nouveau).
     * POST /api/auth/refresh-token
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();

        // Révoquer le token actuel
        $user->currentAccessToken()->delete();

        $newToken = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Token renouvelé avec succès.',
            'data'    => [
                'token'      => $newToken,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Inscription d'un client.
     * POST /api/auth/register/client
     */
    public function registerClient(RegisterClientRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'role'              => 'client',
            'validation_status' => 'validated',
        ]);

        $user  = $this->userRepository->create($data);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Compte client créé.',
            'data'    => [
                'user'       => new UserResource($user),
                'token'      => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    /**
     * Inscription d'un prestataire (en attente de validation).
     * POST /api/auth/register/provider
     */
    public function registerProvider(RegisterProviderRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'role'              => 'provider',
            'validation_status' => 'pending',
        ]);

        $user = $this->userRepository->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Inscription soumise. En attente de validation par un administrateur.',
            'data'    => [
                'user' => new UserResource($user),
            ],
        ], 201);
    }
}
