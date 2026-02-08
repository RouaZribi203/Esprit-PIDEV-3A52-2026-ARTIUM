<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReclamController extends AbstractController
{
    #[Route('/reclamation', name: 'reclamations')]
    public function index(): Response
    {
        return $this->render('reclam/reclams.html.twig', [
            'controller_name' => 'ReclamController',
        ]);
    }
}
