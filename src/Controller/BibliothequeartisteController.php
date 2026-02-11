<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BibliothequeartisteController extends AbstractController
{
    #[Route('/artiste-bibliotheque', name: 'app_bibliothequeartiste')]
    public function index(): Response
    {
        return $this->render('Front Office/bibliothequeartiste/bibliothequeartiste.html.twig', [
            'controller_name' => 'BibliothequeartisteController',
        ]);
    }
}
