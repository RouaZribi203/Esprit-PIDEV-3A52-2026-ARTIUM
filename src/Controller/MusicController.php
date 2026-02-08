<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MusicController extends AbstractController
{
    #[Route('/musique', name: 'musiques')]
    public function index(): Response
    {
        return $this->render('music/music.html.twig', [
            'controller_name' => 'MusicController',
        ]);
    }
}
