<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EventsfrontController extends AbstractController
{
    #[Route('/user-evenements', name: 'app_eventsfront')]
    public function index(): Response
    {
        return $this->render('Front Office/eventsfront/eventsfront.html.twig', [
            'controller_name' => 'EventsfrontController',
        ]);
    }
}
