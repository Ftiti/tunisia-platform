<?php

namespace App\Http\Controllers\Api;

use App\AI\Agents\ChatbotAgent;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    public function __construct(
        private readonly ChatbotAgent $agent,
    ) {}

    /**
     * POST /api/ai/chat
     *
     * Body: { session_id?, message, user_id? }
     */
    public function message(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'nullable|string|max:100',
            'message'    => 'required|string|min:1|max:2000',
            'user_id'    => 'nullable|integer',
        ]);

        // Générer un session_id si non fourni
        $sessionId = $validated['session_id'] ?? (string) Str::uuid();
        $userId    = isset($validated['user_id']) ? (int) $validated['user_id'] : null;

        $role   = $request->user()?->role ?? 'client';
        $result = $this->agent->respond($sessionId, $validated['message'], $request->user()?->id ?? $userId, $role);

        return response()->json([
            'success' => true,
            'message' => 'Réponse générée.',
            'data'    => $result,
        ]);
    }

    /**
     * DELETE /api/ai/chat/{session_id}
     */
    public function clear(string $sessionId): JsonResponse
    {
        $deleted = $this->agent->clear($sessionId);

        return response()->json([
            'success' => $deleted,
            'message' => $deleted ? 'Conversation effacée.' : 'Conversation introuvable.',
            'data'    => null,
        ]);
    }
}
