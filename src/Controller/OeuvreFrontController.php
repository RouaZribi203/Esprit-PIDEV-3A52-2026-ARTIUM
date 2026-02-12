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
    public function index(OeuvreRepository $oeuvreRepository): Response
    {   $oeuvre = new Oeuvre();
        $oeuvres = $oeuvreRepository->findAll();
        $processedOeuvres = [];
        $form = $this->createForm(OeuvreType::class, $oeuvre,['include_date' => false,]);
        //$formEdit = [];
        //foreach ($oeuvres as $oeuvre) {
        ///$formEdit[$oeuvre->getId()] = $this->createForm(OeuvreType::class, $oeuvre)->createView();
       // }
        
        foreach ($oeuvres as $oeuvre) {
            $image = $oeuvre->getImage();
            
            if ($image) {
                // Handle resource streams
                if (is_resource($image)) {
                    rewind($image);
                    $imageData = stream_get_contents($image);
                } else {
                    $imageData = $image;
                }
                
                // Only process if we have actual image data
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
            //'formEdit' => $formEdit, 
            'oeuvres' => $oeuvreRepository->findAll(),
            'typeOeuvres' => TypeOeuvre::cases(),
            'processedOeuvres' => $processedOeuvres,
        ]);
    }
    #[Route('/new_oeuvre', name: 'app_oeuvre_new', methods: ['GET','POST'])]
    public function new(Request $request,EntityManagerInterface $entityManager,UserRepository $userRepository): Response {
        
        $oeuvre = new Oeuvre();
        $form = $this->createForm(OeuvreType::class, $oeuvre,['include_date' => false,]);
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

       return $this->render('oeuvre_front/oeuvre_front.html.twig', [
        'form' => $form->createView(),]);
    }
    #[Route('/oeuvre/{id}', name: 'app_oeuvre_delete', methods: ['POST'])]
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
