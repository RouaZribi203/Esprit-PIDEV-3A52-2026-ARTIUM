<?php

namespace App\Controller;

use App\Entity\Oeuvre;
use App\Entity\User;
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
    public function index(OeuvreRepository $oeuvreRepository, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        $oeuvre = new Oeuvre();
        $oeuvres = [];
        if ($user && method_exists($user, 'getCollections')) {
            foreach ($user->getCollections() as $collection) {
                if (method_exists($collection, 'getOeuvres')) {
                    foreach ($collection->getOeuvres() as $oeuvreItem) {
                        $oeuvres[] = $oeuvreItem;
                    }
                }
            }
        }
        $processedOeuvres = [];
        $form = $this->createForm(OeuvreType::class, $oeuvre, [
            'include_date' => false,
            'user' => $user,
        ]);
        foreach ($oeuvres as $oeuvre) {
            $image = $oeuvre->getImage();
            if ($image) {
                if (is_resource($image)) {
                    rewind($image);
                    $imageData = stream_get_contents($image);
                } else {
                    $imageData = $image;
                }
                if ($imageData && strlen($imageData) > 0) {
                    $imageBase64 = base64_encode($imageData);
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->buffer($imageData);
                    $processedOeuvres[$oeuvre->getId()] = [
                        'imageBase64' => $imageBase64,
                        'mimeType' => $mimeType ?: 'image/jpeg',
                    ];
                }
            }
        }
        return $this->render('oeuvre_front/oeuvre_front.html.twig', [
            'controller_name' => 'OeuvreFrontController',
            'form' => $form->createView(),
            'oeuvres' => $oeuvres,
            'typeOeuvres' => TypeOeuvre::cases(),
            'processedOeuvres' => $processedOeuvres,
        ]);
    }
    #[Route('/new_oeuvre', name: 'app_oeuvre_new', methods: ['GET','POST'])]
    public function new(Request $request,EntityManagerInterface $entityManager,UserRepository $userRepository): Response {
        
        $oeuvre = new Oeuvre();
        $user = $this->getUser();
        $form = $this->createForm(OeuvreType::class, $oeuvre, [
            'include_date' => false,
            'user' => $user,
        ]);
        $form->handleRequest($request);

       if ($form->isSubmitted() && $form->isValid()) {

        
        $imageFile = $form->get('image')->getData();

        if ($imageFile) {
            // Read the binary content of the file
            $blobData = fopen($imageFile->getPathname(), 'rb');
            $oeuvre->setImage($blobData);
        }
        $oeuvre->setDateCreation(new \DateTime());
        $entityManager->persist($oeuvre);
        $entityManager->flush();

        $this->addFlash('success', 'Œuvre ajoutée avec succès 🎨');

        return $this->redirectToRoute('app_oeuvre_front');
       }

       $user = $this->getUser();
       $oeuvres = [];
       if ($user && method_exists($user, 'getCollections')) {
           foreach ($user->getCollections() as $collection) {
               if (method_exists($collection, 'getOeuvres')) {
                   foreach ($collection->getOeuvres() as $oeuvreItem) {
                       $oeuvres[] = $oeuvreItem;
                   }
               }
           }
       }
       $processedOeuvres = [];
       foreach ($oeuvres as $oeuvre) {
           $image = $oeuvre->getImage();
           if ($image) {
               if (is_resource($image)) {
                   rewind($image);
                   $imageData = stream_get_contents($image);
               } else {
                   $imageData = $image;
               }
               if ($imageData && strlen($imageData) > 0) {
                   $imageBase64 = base64_encode($imageData);
                   $finfo = new \finfo(FILEINFO_MIME_TYPE);
                   $mimeType = $finfo->buffer($imageData);
                   $processedOeuvres[$oeuvre->getId()] = [
                       'imageBase64' => $imageBase64,
                       'mimeType' => $mimeType ?: 'image/jpeg',
                   ];
               }
           }
       }
       return $this->render('oeuvre_front/oeuvre_front.html.twig', [
           'form' => $form->createView(),
           'oeuvres' => $oeuvres,
           'typeOeuvres' => TypeOeuvre::cases(),
           'processedOeuvres' => $processedOeuvres,
       ]);
    }
    #[Route('/oeuvre/{id}', name: 'app_oeuvre_delete_front', methods: ['POST'])]
    public function delete(Request $request, Oeuvre $oeuvre, EntityManagerInterface $em): Response
    {
        $likes = $oeuvre->getLikes(); 
        foreach ($likes as $like) {$em->remove($like);}
        if ($this->isCsrfTokenValid('delete'.$oeuvre->getId(),$request->getPayload()->getString('_token'))) {
        $em->remove($oeuvre);
        $em->flush();
        }

        return $this->redirectToRoute('app_oeuvre_front');
    }


    #[Route('/{id}/edit_oeuvre', name: 'oeuvre_edit', methods: ['GET','POST'])]
    public function edit(Request $request,EntityManagerInterface $entityManager,UserRepository $userRepository,OeuvreRepository $oeuvreRepository): Response {
        
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
        
        $entityManager->persist($oeuvre);
        $entityManager->flush();

        $this->addFlash('success', 'Œuvre ajoutée avec succès 🎨');

        return $this->redirectToRoute('app_oeuvre_front');
       }

       return $this->render('oeuvre_front/oeuvre_front.html.twig', [
        'form' => $form->createView(),'oeuvres' => $oeuvreRepository->findAll(),]);
    }


}
