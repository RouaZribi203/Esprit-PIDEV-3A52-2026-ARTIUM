<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Service\FaceRecognitionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final class SigninController extends AbstractController
{
    #[Route('/signin', name: 'app_signin')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        // Si déjà connecté, rediriger
        if ($this->getUser()) {
            return $this->redirectToRoute('app_admin');
        }

        // Récupérer l'erreur de connexion
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Dernier email saisi
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('signin/signin.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        
    }

    #[Route('/face-signin', name: 'app_face_signin', methods: ['POST'])]
    public function faceSignin(
        Request $request,
        FaceRecognitionService $faceService,
        UserAuthenticatorInterface $userAuthenticator,
        \App\Security\FaceAuthenticator $faceAuthenticator,
        \Doctrine\ORM\EntityManagerInterface $em
    ): Response {
        $uploadedImage = $request->files->get('photo');
        $email = $request->request->get('email');
        if (!$uploadedImage || !$email) {
            return new Response("Utilisateur ou photo manquant", 400);
        }
        // Récupérer l'utilisateur par email
        $user = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return new Response("Utilisateur non trouvé", 404);
        }
        $photoReferenceFilename = $user->getPhotoReferencePath();
        if (!$photoReferenceFilename) {
            return new Response("Aucune photo de référence", 400);
        }
        $photoDir = $this->getParameter('user_photos_directory');
        $referenceFile = $photoDir . '/' . $photoReferenceFilename;
        if (!file_exists($referenceFile)) {
            return new Response("La photo de référence n'existe pas", 500);
        }
        $result = $faceService->compare(
            $uploadedImage->getPathname(),
            $referenceFile
        );
        if ($result) {
            // Connexion automatique
            return $userAuthenticator->authenticateUser($user, $faceAuthenticator, $request);
        }
        return new Response("Face Not Recognized", 401);
    }
}