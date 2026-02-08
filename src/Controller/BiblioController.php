<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BiblioController extends AbstractController
{
    #[Route('/bibliotheque', name: 'livres')]
    public function index(): Response
    {
        return $this->render('biblio/livres.html.twig', [
            'controller_name' => 'BiblioController',
        ]);
    }
}
