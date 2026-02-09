<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BibliofrontController extends AbstractController
{
    #[Route('/user-bibliotheque', name: 'app_bibliofront')]
    public function index(): Response
    {
        return $this->render('Front Office/bibliofront/bibliofront.html.twig', [
            'controller_name' => 'BibliofrontController',
        ]);
    }
}
