<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CollectionsFrontController extends AbstractController
{
    #[Route('/artiste-collections', name: 'app_collections_front')]
    public function index(): Response
    {
        return $this->render('Front Office/collections_front/collectionsfront.html.twig', [
            'controller_name' => 'CollectionsFrontController',
        ]);
    }
}
