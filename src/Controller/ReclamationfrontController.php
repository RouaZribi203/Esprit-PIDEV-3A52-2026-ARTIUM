<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReclamationfrontController extends AbstractController
{
    #[Route('/user-reclamation', name: 'app_reclamationfront')]
    public function index(): Response
    {
        return $this->render('Front Office/reclamationfront/reclamationfront.html.twig', [
            'controller_name' => 'ReclamationfrontController',
        ]);
    }
}
