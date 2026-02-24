<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class HuggingFaceAIService
{
    // Nouvelle API Chat Completions de Hugging Face
    private const API_URL = 'https://router.huggingface.co/v1/chat/completions';
    
    // Modèles à essayer en ordre de préférence
    private const MODELS = [
        'mistralai/Mistral-7B-Instruct-v0.2',
        'microsoft/phi-2',
        'google/flan-t5-large',
        'meta-llama/Llama-2-7b-chat-hf',
    ];
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $huggingFaceApiKey
    ) {
    }

    /**
     * Génère une réponse IA pour une réclamation
     */
    public function generateResponse(string $reclamationText, string $type, string $userName): ?string
    {
        // Essayer chaque modèle jusqu'à ce qu'un fonctionne
        foreach (self::MODELS as $model) {
            try {
                $this->logger->info('Tentative Hugging Face Chat API', ['model' => $model]);
                
                $response = $this->httpClient->request('POST', self::API_URL, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->huggingFaceApiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => $model,
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'Tu es un assistant administratif professionnel pour ARTIUM, une plateforme d\'art. Tu réponds aux réclamations de manière empathique et professionnelle en français.'
                            ],
                            [
                                'role' => 'user',
                                'content' => $this->buildUserPrompt($reclamationText, $type, $userName)
                            ]
                        ],
                        'max_tokens' => 300,
                        'temperature' => 0.7,
                        'top_p' => 0.9,
                    ],
                    'timeout' => 30,
                ]);

                $statusCode = $response->getStatusCode();
                
                if ($statusCode === 200) {
                    $data = $response->toArray();
                    
                    // Format de réponse Chat Completions
                    if (isset($data['choices'][0]['message']['content'])) {
                        $text = trim($data['choices'][0]['message']['content']);
                        $this->logger->info('Hugging Face Chat API - Succès', [
                            'model' => $model,
                            'length' => strlen($text)
                        ]);
                        return $this->cleanResponse($text);
                    }
                }
                
            } catch (\Exception $e) {
                $this->logger->warning('Hugging Face model failed', [
                    'model' => $model,
                    'error' => $e->getMessage()
                ]);
                continue; // Essayer le prochain modèle
            }
        }
        
        // Si aucun modèle ne fonctionne, retourner null (le fallback prendra le relais)
        $this->logger->error('Tous les modèles Hugging Face ont échoué');
        return null;
    }

    private function buildUserPrompt(string $text, string $type, string $userName): string
    {
        return <<<PROMPT
Réclamation de type "$type" de $userName:
"$text"

Rédige une réponse professionnelle, empathique et personnalisée en français. La réponse doit:
- Commencer par "Bonjour $userName,"
- Reconnaître précisément le problème mentionné
- Proposer une solution concrète
- Être concise (2-3 paragraphes maximum)
- Se terminer par "Cordialement, L'équipe ARTIUM"
PROMPT;
    }

    private function cleanResponse(string $response): string
    {
        $response = trim($response);
        
        // Supprimer les guillemets si présents
        if (str_starts_with($response, '"') && str_ends_with($response, '"')) {
            $response = substr($response, 1, -1);
        }
        
        // Limiter la longueur
        if (strlen($response) > 1000) {
            $response = substr($response, 0, 997) . '...';
        }
        
        return $response;
    }
}
