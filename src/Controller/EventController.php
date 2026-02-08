<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EventController extends AbstractController
{
    #[Route('/evenement', name: 'evenements')]
    public function index(): Response
    {
        return $this->render('event/events.html.twig', [
            'controller_name' => 'EventController',
        ]);
    }
}
