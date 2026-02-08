<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ForgotPWDController extends AbstractController
{   
    #[Route('/forgotpwd', name: 'app_forgot_pwd')]
    public function index(): Response
    {
        return $this->render('forgot_pwd/forgotpwd.html.twig', [
            'controller_name' => 'ForgotPWDController',
        ]);
    }
}
