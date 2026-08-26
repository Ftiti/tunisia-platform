<?php

namespace App\AI\Agents;

use App\AI\OllamaClient;
use App\Http\Clients\BookingServiceClient;
use App\Models\AiConversation;

class ChatbotAgent
{
    private const MAX_HISTORY = 20; // messages max en mémoire (10 échanges)

    private const SYSTEM_PROMPT_CLIENT = 'Tu es l\'assistant virtuel de Tunisia Services en français et darija tunisienne. Tu aides les utilisateurs à: trouver des prestataires de services, comprendre et gérer leurs réservations, avoir des informations sur la plateforme. Réponds de manière utile et concise (max 3 phrases). Si quelqu\'un demande de l\'aide pour une urgence, recommande d\'appeler directement.';

    private const SYSTEM_PROMPT_PROVIDER = 'Tu es l\'assistant virtuel Tunisia Services pour les prestataires professionnels, en français. Tu aides les prestataires à: gérer leur profil et services, comprendre les réservations et le système de commission, optimiser leur visibilité sur la plateforme, gérer leurs produits et abonnements. Réponds de manière professionnelle et concise (max 3 phrases).';

    public function __construct(
        private readonly OllamaClient          $ollama,
        private readonly BookingServiceClient  $bookingClient,
    ) {}

    /**
     * Répondre à un message utilisateur.
     */
    public function respond(string $sessionId, string $userMessage, ?int $userId = null, string $role = 'client'): array
    {
        // Choisir le prompt système selon le rôle
        $systemPrompt = ($role === 'provider')
            ? self::SYSTEM_PROMPT_PROVIDER
            : self::SYSTEM_PROMPT_CLIENT;

        // Récupérer ou créer la conversation
        $conversation = AiConversation::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'type'     => 'chatbot',
                'messages' => [],
                'user_id'  => $userId,
            ]
        );

        // Détecter l'intention
        $intent = $this->detectIntent($userMessage);

        // Enrichir le contexte selon l'intention
        $contextMessages = $this->buildContextMessages($intent, $userMessage, $userId);

        // Tronquer l'historique pour éviter les tokens excessifs
        $history = array_slice($conversation->messages ?? [], -self::MAX_HISTORY);

        // Construire les messages pour Ollama
        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $history,
            $contextMessages,
            [['role' => 'user', 'content' => $userMessage]]
        );

        $response = $this->ollama->chat(
            config('services.ollama.model_chat', 'mistral:7b'),
            $messages
        );

        // Fallback si Ollama ne répond pas
        if (empty($response)) {
            $response = "Je suis désolé, je rencontre des difficultés techniques. Veuillez réessayer dans quelques instants.";
        }

        // Sauvegarder les nouveaux messages dans l'historique
        $updatedMessages = array_merge($history, [
            ['role' => 'user',      'content' => $userMessage],
            ['role' => 'assistant', 'content' => $response],
        ]);

        $conversation->update([
            'messages' => $updatedMessages,
            'user_id'  => $userId ?? $conversation->user_id,
        ]);

        return [
            'session_id' => $sessionId,
            'message'    => $response,
            'intent'     => $intent,
        ];
    }

    /**
     * Effacer l'historique d'une conversation.
     */
    public function clear(string $sessionId): bool
    {
        return (bool) AiConversation::where('session_id', $sessionId)->delete();
    }

    /**
     * Détecter l'intention du message utilisateur.
     */
    private function detectIntent(string $message): string
    {
        $result = $this->ollama->chatJSON(
            config('services.ollama.model_classify', 'mistral:7b'),
            [
                [
                    'role'    => 'user',
                    'content' => <<<PROMPT
                    Détecte l'intention de ce message en français ou arabe tunisien.
                    Retourne exactement ce JSON:
                    {"intent": "booking" ou "cancel" ou "info" ou "search" ou "complaint" ou "other"}

                    Message: {$message}
                    PROMPT,
                ],
            ]
        );

        $valid = ['booking', 'cancel', 'info', 'search', 'complaint', 'other'];
        $intent = $result['intent'] ?? 'other';

        return in_array($intent, $valid, true) ? $intent : 'other';
    }

    /**
     * Enrichir le contexte avec les données réelles si pertinent.
     */
    private function buildContextMessages(string $intent, string $message, ?int $userId): array
    {
        $contextMessages = [];

        if ($intent === 'booking' || $intent === 'cancel') {
            // Extraire un numéro de réservation mentionné dans le message
            if (preg_match('/\b(\d{4,})\b/', $message, $matches)) {
                $booking = $this->bookingClient->getBooking((int) $matches[1]);
                if ($booking) {
                    $contextMessages[] = [
                        'role'    => 'system',
                        'content' => 'Données de la réservation mentionnée: ' . json_encode($booking, JSON_UNESCAPED_UNICODE),
                    ];
                }
            }

            // Si on a l'userId, récupérer les dernières réservations
            if ($userId) {
                $bookings = $this->bookingClient->getUserBookings($userId);
                if (!empty($bookings)) {
                    $summary = array_slice($bookings, 0, 3); // 3 dernières seulement
                    $contextMessages[] = [
                        'role'    => 'system',
                        'content' => 'Dernières réservations de l\'utilisateur: ' . json_encode($summary, JSON_UNESCAPED_UNICODE),
                    ];
                }
            }
        }

        return $contextMessages;
    }
}
