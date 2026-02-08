<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GalerieController extends AbstractController
{
    #[Route('/galerie', name: 'galeries')]
    public function index(): Response
    {
        return $this->render('galerie/galeries.html.twig', [
            'controller_name' => 'GalerieController',
        ]);
    }
}
