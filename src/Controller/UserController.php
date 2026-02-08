<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/amateurs', name: 'amateurs')]
    public function index(): Response
    {
        return $this->render('user/user.html.twig', [
            'controller_name' => 'UserController',
            'type' => 'mateur',
        ]);
    }
    #[Route('/artistes', name: 'artistes')]
    public function indexx(): Response
    {
        return $this->render('user/user.html.twig', [
            'controller_name' => 'UserController',
            'type' => 'rtiste',
        ]);
    }
}
