<?php

namespace App\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('services.ollama.url', 'http://host.docker.internal:11434'),
            '/'
        );
    }

    /**
     * Chat avec le modèle — retourne le contenu texte brut.
     */
    public function chat(string $model, array $messages, array $options = []): string
    {
        try {
            $payload = [
                'model'    => $model,
                'messages' => $messages,
                'stream'   => false,
            ];

            if (!empty($options)) {
                $payload['options'] = $options;
            }

            $response = Http::timeout(120)
                ->post("{$this->baseUrl}/api/chat", $payload);

            if ($response->failed()) {
                Log::error('Ollama chat error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'model'  => $model,
                ]);
                return '';
            }

            return $response->json('message.content') ?? '';

        } catch (\Throwable $e) {
            Log::error('Ollama connection error', [
                'error' => $e->getMessage(),
                'model' => $model,
            ]);
            return '';
        }
    }

    /**
     * Chat avec JSON forcé — retourne un tableau associatif.
     * Injecte un system prompt pour forcer la réponse en JSON valide.
     */
    public function chatJSON(string $model, array $messages): array
    {
        $systemMessage = [
            'role'    => 'system',
            'content' => 'Tu es un assistant. Réponds UNIQUEMENT en JSON valide. Sans markdown. Sans explication. Sans texte avant ou après le JSON.',
        ];

        $content = $this->chat($model, array_merge([$systemMessage], $messages), [
            'temperature' => 0.1,
        ]);

        if (empty($content)) {
            return [];
        }

        // Nettoyer les blocs markdown si présents
        $clean = preg_replace('/```json\s*/i', '', $content);
        $clean = preg_replace('/```\s*/i', '', $clean);
        $clean = trim($clean);

        // Extraire le premier objet JSON valide
        if (preg_match('/\{.*\}/s', $clean, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        Log::warning('Ollama chatJSON: impossible de parser le JSON', [
            'raw'   => $content,
            'model' => $model,
        ]);

        return [];
    }

    /**
     * Vérifie si Ollama est disponible.
     */
    public function isAvailable(): bool
    {
        try {
            return Http::timeout(3)->get("{$this->baseUrl}/api/tags")->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Liste les modèles installés.
     */
    public function listModels(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/api/tags");
            return $response->json('models') ?? [];
        } catch (\Throwable) {
            return [];
        }
    }
}
