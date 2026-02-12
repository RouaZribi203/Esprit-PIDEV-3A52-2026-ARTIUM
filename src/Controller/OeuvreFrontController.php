<?php

namespace App\Controller;

use App\Entity\Oeuvre;
use App\Enum\TypeOeuvre;
use App\Form\OeuvreType;
use App\Repository\OeuvreRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class OeuvreFrontController extends AbstractController
{
    #[Route('/mes_oeuvres', name: 'app_oeuvre_front')]
    public function index(): Response
    {   $oeuvre = new Oeuvre();
        $form = $this->createForm(OeuvreType::class, $oeuvre);
        return $this->render('oeuvre_front/oeuvre_front.html.twig', [
            'controller_name' => 'OeuvreFrontController',
            'form' => $form->createView(),
        ]);
    }
    #[Route('/oeuvre/new', name: 'app_oeuvre_new', methods: ['POST'])]
    public function new(Request $request,EntityManagerInterface $entityManager,UserRepository $userRepository): Response {
        $oeuvre = new Oeuvre();
        $form = $this->createForm(OeuvreType::class, $oeuvre);
        $form->handleRequest($request);

       if ($form->isSubmitted() && $form->isValid()) {

        
        $imageFile = $form->get('image')->getData();

        if ($imageFile) {
            // Read the binary content of the file
            $blobData = fopen($imageFile->getPathname(), 'rb');
            $oeuvre->setImage($blobData);
        }

        $user = $userRepository->find(2);
        $oeuvre->setArtiste($user);

        $entityManager->persist($oeuvre);
        $entityManager->flush();

        $this->addFlash('success', 'Œuvre ajoutée avec succès 🎨');

        return $this->redirectToRoute('app_oeuvre_front'); // change to your route
       }

       return $this->render('oeuvre_front/oeuvre_front.html.twig', [
        'form' => $form->createView(),]);
    }


}
