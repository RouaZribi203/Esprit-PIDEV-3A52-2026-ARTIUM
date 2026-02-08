<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FeedController extends AbstractController
{
    #[Route('/feed', name: 'app_feed')]
    public function index(): Response
    {
        return $this->render('Front Office/feed/feed.html.twig', [
            'controller_name' => 'FeedController',
        ]);
    }
}
