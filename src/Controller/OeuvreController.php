<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OeuvreController extends AbstractController
{
    #[Route('/oeuvre', name: 'oeuvres')]
    public function index(): Response
    {
        return $this->render('oeuvre/oeuvres.html.twig', [
            'controller_name' => 'OeuvreController',
        ]);
    }
}
