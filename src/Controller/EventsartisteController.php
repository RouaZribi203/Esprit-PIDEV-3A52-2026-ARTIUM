<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EventsartisteController extends AbstractController
{
    #[Route('/artiste-evenements', name: 'app_eventsartiste')]
    public function index(): Response
    {
        return $this->render('Front Office/eventsartiste/eventartiste.html.twig', [
            'controller_name' => 'EventsartisteController',
        ]);
    }
}
