<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\SignupType;
use App\Entity\User;
use App\Enum\Statut;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class SignupController extends AbstractController
{
    #[Route('/signup', name: 'app_signup')]
    public function index(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(SignupType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion du mot de passe
            $plainPassword = $user->getPlainPassword();
            $user->setMdp($passwordHasher->hashPassword($user, (string) $plainPassword));

            // Date inscription
            $user->setDateInscription(new \DateTime());

            // Statut par defaut
            $user->setStatut(Statut::ACTIVE);

            // Personnalisation selon le rôle
            if ($user->getRole() === \App\Enum\Role::AMATEUR) {
                $user->setSpecialite(null);
            } elseif ($user->getRole() === \App\Enum\Role::ARTISTE) {
                $user->setCentreInteret(null);
            } elseif ($user->getRole() === \App\Enum\Role::ADMIN) {
                $user->setSpecialite(null);
                $user->setCentreInteret(null);
            }

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', "Inscription réussie ! Vous pouvez maintenant vous connecter.");
            return $this->redirectToRoute('app_signin');
        }

        return $this->render('signup/signup.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
