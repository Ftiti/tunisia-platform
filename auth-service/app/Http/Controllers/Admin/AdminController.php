<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Liste des admins avec filtres et pagination.
     * GET /api/admin/users
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->whereIn('role', ['super_admin', 'admin', 'manager', 'moderator']);

        // Filtre recherche (nom ou email)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name',  'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        // Filtre par rôle
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filtre par statut actif/inactif
        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', (bool) $request->is_active);
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $users   = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Admins récupérés avec succès.',
            'data'    => [
                'users'        => UserResource::collection($users->items()),
                'total'        => $users->total(),
                'per_page'     => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
            ],
        ]);
    }

    /**
     * Détail d'un admin.
     * GET /api/admin/users/{id}
     */
    public function show(User $user): JsonResponse
    {
        $this->assertIsAdmin($user);

        return response()->json([
            'success' => true,
            'message' => 'Admin récupéré.',
            'data'    => new UserResource($user),
        ]);
    }

    /**
     * Créer un nouvel admin.
     * POST /api/admin/users
     */
    public function store(StoreAdminRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Admin créé avec succès.',
            'data'    => new UserResource($user),
        ], 201);
    }

    /**
     * Mettre à jour un admin.
     * PUT /api/admin/users/{id}
     */
    public function update(UpdateAdminRequest $request, User $user): JsonResponse
    {
        $this->assertIsAdmin($user);

        // Empêcher la modification de son propre rôle/statut
        if ($user->id === auth()->id()) {
            $validated = collect($request->validated())
                ->except(['role', 'is_active'])
                ->toArray();
        } else {
            $validated = $request->validated();
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Admin mis à jour avec succès.',
            'data'    => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Supprimer un admin.
     * DELETE /api/admin/users/{id}
     */
    public function destroy(User $user): JsonResponse
    {
        $this->assertIsAdmin($user);

        // Empêcher l'auto-suppression
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.',
                'data'    => null,
            ], 403);
        }

        $user->tokens()->delete(); // Révoquer tous ses tokens Sanctum
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Admin supprimé avec succès.',
            'data'    => null,
        ]);
    }

    /**
     * Retourner la liste des permissions disponibles.
     * GET /api/admin/permissions
     */
    public function permissions(): JsonResponse
    {
        $permissions = [
            'users.view',    'users.create',    'users.edit',    'users.delete',
            'providers.view','providers.create','providers.edit','providers.delete',
            'orders.view',   'orders.edit',
            'payments.view', 'payments.refund',
            'settings.view', 'settings.edit',
            'reports.view',  'reports.export',
        ];

        return response()->json([
            'success' => true,
            'message' => 'Permissions récupérées.',
            'data'    => ['permissions' => $permissions],
        ]);
    }

    // Vérifie que l'utilisateur cible est bien un admin (pas un client/livreur)
    private function assertIsAdmin(User $user): void
    {
        if (! in_array($user->role, ['super_admin', 'admin', 'manager', 'moderator'])) {
            abort(404, 'Admin non trouvé.');
        }
    }
}
