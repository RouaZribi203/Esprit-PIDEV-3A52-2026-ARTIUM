<?php

namespace App\Controller;

use App\Repository\MusiqueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MusicController extends AbstractController
{
    #[Route('/musique', name: 'musiques')]
    public function index(Request $request, MusiqueRepository $musiqueRepository): Response
    {
        // Get search and sort parameters
        $searchTerm = $request->query->get('search');
        $sortBy = $request->query->get('sort', 'date');
        $sortOrder = $request->query->get('order', 'DESC');
        
        // Fetch music with search/sort
        $musiques = $musiqueRepository->searchAndFilter($searchTerm, $sortBy, $sortOrder);
        
        return $this->render('music/music.html.twig', [
            'musiques' => $musiques,
            'searchTerm' => $searchTerm,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }
}
