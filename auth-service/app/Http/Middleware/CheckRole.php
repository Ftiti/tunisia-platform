<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Vérifie que l'utilisateur authentifié possède l'un des rôles autorisés.
     * Usage dans les routes: ->middleware('role:admin,provider')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié.',
                'data'    => null,
            ], 401);
        }

        if (! $request->user()->hasRole($roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Rôle insuffisant.',
                'data'    => null,
            ], 403);
        }

        return $next($request);
    }
}
