<?php

namespace App\AI\Agents;

use App\AI\OllamaClient;
use App\Models\AiClassification;

class ClassificationAgent
{
    public function __construct(
        private readonly OllamaClient $ollama,
    ) {}

    /**
     * Classifier automatiquement un prestataire.
     */
    public function classify(array $providerData): array
    {
        $name        = $providerData['name']        ?? '';
        $description = $providerData['description'] ?? 'Aucune description fournie.';

        $result = $this->ollama->chatJSON(
            config('services.ollama.model_classify', 'mistral:7b'),
            [
                [
                    'role'    => 'user',
                    'content' => <<<PROMPT
                    Tu es un expert en classification de prestataires de services en Tunisie.
                    Classifie ce prestataire et retourne exactement ce JSON:
                    {
                      "category": "nom de catégorie en français",
                      "suggested_services": [
                        {"name": "nom du service", "price_min": 0, "price_max": 0, "duration_minutes": 60}
                      ],
                      "tags": ["tag1", "tag2", "tag3"],
                      "confidence": 0.95
                    }

                    Catégories disponibles: Plomberie, Électricité, Médecine, Restaurant, Coiffure, Nettoyage, Informatique, Transport, Beauté, Éducation, Autre

                    Prestataire:
                    Nom: {$name}
                    Description: {$description}
                    PROMPT,
                ],
            ]
        );

        // Valeurs par défaut si le parsing échoue
        $result = array_merge([
            'category'          => 'Autre',
            'suggested_services' => [],
            'tags'              => [],
            'confidence'        => 0.0,
        ], $result);

        // Persister en base
        if (isset($providerData['id'])) {
            AiClassification::create([
                'provider_id' => $providerData['id'],
                'result'      => $result,
                'confidence'  => (float) ($result['confidence'] ?? 0),
                'is_applied'  => false,
            ]);
        }

        return $result;
    }

    /**
     * Appliquer une classification à un prestataire (marquer is_applied = true).
     */
    public function markApplied(int $classificationId): bool
    {
        return (bool) AiClassification::where('id', $classificationId)
            ->update(['is_applied' => true]);
    }
}
