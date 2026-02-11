<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReclamationsartisteController extends AbstractController
{
    #[Route('/reclamationsartiste', name: 'app_reclamationsartiste')]
    public function index(): Response
    {
        return $this->render('Front Office/reclamationsartiste/reclamationsartiste.html.twig', [
            'controller_name' => 'ReclamationsartisteController',
        ]);
    }
}
