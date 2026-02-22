<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\OeuvreRepository;
use App\Enum\CentreInteret;
use App\Enum\TypeOeuvre;

class RecommendationService
{
    private OeuvreRepository $oeuvreRepository;

    public function __construct(OeuvreRepository $oeuvreRepository)
    {
        $this->oeuvreRepository = $oeuvreRepository;
    }

    /**
     * Temporary implementation:
     * just returns user-interacted œuvres
     */
    public function getInteractedOeuvresForUser(User $user): array
    {
        return $this->oeuvreRepository->findRecommendedForUser($user);
    }
    public function getRecommendedOeuvres(User $user,int $topN = 3): array
    {
        // 1️⃣ Get user-interacted œuvres
        $interactedOeuvres = $this->oeuvreRepository->findRecommendedForUser($user);
        $validEmbeddings = [];
        foreach ($interactedOeuvres as $oeuvre) {
            $embedding = $oeuvre->getEmbedding(); // array of floats
                if (is_array($embedding) && count($embedding) > 0) {
                    $validEmbeddings[] = $embedding;
                }
        }
        //If no valid embeddings, fallback based on user interests
        if (count($validEmbeddings) === 0) {
            $centreInterets = $user->getCentreInteret() ?? [];
            $mappedTypes = [];
            foreach ($centreInterets as $ci) {
                $ciValue = $ci instanceof CentreInteret ? $ci->value : (string) $ci;
                switch ($ciValue) {
                case CentreInteret::PEINTURE->value:
                    $mappedTypes[] = TypeOeuvre::PEINTURE->value;
                    break;
                case CentreInteret::SCULPTURE->value:
                    $mappedTypes[] = TypeOeuvre::SCULPTURE->value;
                    break;
                case CentreInteret::PHOTOGRAPHIE->value:
                    $mappedTypes[] = TypeOeuvre::PHOTOGRAPHIE->value;
                    break;
                case CentreInteret::MUSIQUE->value:
                    $mappedTypes[] = TypeOeuvre::MUSIQUE->value;
                    break;
                case CentreInteret::LECTURE->value:
                    $mappedTypes[] = TypeOeuvre::LIVRE->value;
                    break;
                }
            }
            $mappedTypes = array_unique($mappedTypes);
            // return directement les œuvres filtrées par type
            return $this->oeuvreRepository->findByTypes($mappedTypes);
        }
        // 2️⃣ Compute user embedding if possible
        $userEmbedding = null;
        if (count($validEmbeddings) > 0) {
            $dimension = count($validEmbeddings[0]);
            $sumVector = array_fill(0, $dimension, 0.0);
            foreach ($validEmbeddings as $embedding) {
                if (count($embedding) !== $dimension) {
                    continue; // skip if dimension mismatch
                }
               for ($i = 0; $i < $dimension; $i++) {
                    $sumVector[$i] += $embedding[$i];
                }
           }
        $userEmbedding = array_map(fn($value) => $value / count($validEmbeddings), $sumVector);
        }
        // 3️⃣ Get candidate œuvres (all artworks except interacted)
        $allOeuvres = $this->oeuvreRepository->findAll(); // or filtered by type
        $candidates = array_filter($allOeuvres, fn($o) => !in_array($o, $interactedOeuvres, true));
        // 4️⃣ Compute similarity for each candidate
        $scores = [];
        foreach ($candidates as $oeuvre) {
            $embedding = $oeuvre->getEmbedding();
            if (!is_array($embedding) || count($embedding) !== $dimension) continue;
            $scores[] = [
                'oeuvre' => $oeuvre,
                'score' => $this->cosineSimilarity($userEmbedding, $embedding)
            ];
        }
        // 5️⃣ Sort by similarity descending
        usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);

        // 6️⃣ Return top N recommended œuvres
        return array_map(fn($s) => $s['oeuvre'], array_slice($scores, 0, $topN));
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        $dim = count($a);
        for ($i = 0; $i < $dim; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }
        if ($normA == 0 || $normB == 0) return 0.0; // avoid division by zero
        return $dot / (sqrt($normA) * sqrt($normB));
    }
}