<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MusiqueartisteController extends AbstractController
{
    #[Route('/musiqueartiste', name: 'app_musiqueartiste')]
    public function index(): Response
    {
        return $this->render('Front Office/musiqueartiste/musiqueartiste.html.twig', [
            'controller_name' => 'MusiqueartisteController',
        ]);
    }
}
