<?php

namespace App\Service;

use App\Entity\Reclamation;

/**
 * Service principal pour générer des suggestions de réponses IA
 */
class AIResponseService
{
    public function __construct(
        private HuggingFaceAIService $huggingFaceService
    ) {
    }

    /**
     * Génère une suggestion de réponse pour une réclamation
     */
    public function generateSuggestion(Reclamation $reclamation): string
    {
        $type = $reclamation->getType() ? $reclamation->getType()->value : 'générale';
        $texte = $reclamation->getTexte();
        $userName = $reclamation->getUser() ? $reclamation->getUser()->getPrenom() : 'Cher client';

        // Essayer d'obtenir une réponse de Hugging Face
        $aiResponse = $this->huggingFaceService->generateResponse($texte, $type, $userName);
        
        if ($aiResponse && strlen($aiResponse) > 50) {
            return $aiResponse;
        }

        // Fallback: Générer une réponse contextuelle locale
        return $this->generateLocalResponse($texte, $type, $userName);
    }

    /**
     * Génère une réponse locale si l'API ne fonctionne pas
     */
    private function generateLocalResponse(string $texte, string $type, string $userName): string
    {
        // Extraire des mots-clés du texte
        $keywords = $this->extractKeywords($texte);
        
        $response = "Bonjour $userName,\n\n";
        
        // Créer une réponse contextuelle basée sur les mots-clés
        if (in_array('urgent', $keywords) || in_array('rapidement', $keywords)) {
            $response .= "Je comprends l'urgence de votre situation. ";
        }
        
        if (in_array('remboursement', $keywords) || in_array('argent', $keywords)) {
            $response .= "Concernant votre demande de remboursement, notre service comptabilité va examiner votre dossier en priorité. Vous recevrez un retour sous 48 heures maximum.\n\n";
        } elseif (in_array('problème', $keywords) || in_array('erreur', $keywords)) {
            $response .= "Nous prenons votre réclamation très au sérieux. Notre équipe technique va analyser ce problème et vous proposer une solution adaptée dans les plus brefs délais.\n\n";
        } else {
            $response .= "Nous avons bien pris en compte votre message concernant $type. Notre équipe va examiner votre situation et vous apporter une réponse personnalisée rapidement.\n\n";
        }
        
        $response .= "Nous vous tiendrons informé(e) de l'avancement de votre dossier.\n\nCordialement,\nL'équipe ARTIUM";
        
        return $response;
    }

    private function extractKeywords(string $text): array
    {
        $text = mb_strtolower($text);
        $keywords = [];
        
        $patterns = [
            'urgent', 'rapidement', 'vite', 'immédiatement',
            'remboursement', 'rembourser', 'argent', 'paiement',
            'problème', 'erreur', 'bug', 'défaut',
            'attente', 'délai', 'retard',
        ];
        
        foreach ($patterns as $pattern) {
            if (str_contains($text, $pattern)) {
                $keywords[] = $pattern;
            }
        }
        
        return $keywords;
    }
}
