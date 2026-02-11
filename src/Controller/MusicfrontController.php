<?php

namespace App\Controller;

use App\Repository\MusiqueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MusicfrontController extends AbstractController
{
    #[Route('/user-musiques', name: 'app_musicfront')]
    public function index(MusiqueRepository $musiqueRepository): Response
    {
        // Fetch all available songs
        $musiques = $musiqueRepository->findAll();
        
        return $this->render('Front Office/music/musicfront.html.twig', [
            'controller_name' => 'MusicfrontController',
            'musiques' => $musiques,
        ]);
    }
}
