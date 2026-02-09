<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EventdetailsController extends AbstractController
{
    #[Route('/details-evenement', name: 'app_eventdetails')]
    public function index(): Response
    {
        return $this->render('Front Office/eventdetails/eventdetails.html.twig', [
            'controller_name' => 'EventdetailsController',
        ]);
    }
}
