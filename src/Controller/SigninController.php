<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SigninController extends AbstractController
{
    #[Route('/signin', name: 'app_signin')]
    public function index(): Response
    {
        return $this->render('signin/signin.html.twig', [
            'controller_name' => 'SigninController',
        ]);
    }
}
