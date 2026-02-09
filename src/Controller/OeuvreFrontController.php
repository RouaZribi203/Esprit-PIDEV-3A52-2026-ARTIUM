<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OeuvreFrontController extends AbstractController
{
    #[Route('/mes_oeuvres', name: 'app_oeuvre_front')]
    public function index(): Response
    {
        return $this->render('oeuvre_front/oeuvre_front.html.twig', [
            'controller_name' => 'OeuvreFrontController',
        ]);
    }
}
