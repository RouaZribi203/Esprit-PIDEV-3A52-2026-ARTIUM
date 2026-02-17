<?php

namespace App\Controller;

use App\Entity\Collections;
use App\Entity\Oeuvre;
use App\Entity\User;
use App\Enum\TypeOeuvre;
use App\Form\OeuvreType;
use App\Repository\CollectionsRepository;
use App\Repository\OeuvreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/oeuvre')]
final class OeuvreController extends AbstractController
{

    #[Route(name: 'oeuvres')]
    public function indexx(OeuvreRepository $oeuvreRepository, CollectionsRepository $collectionsRepository): Response
    {
        

        $peintures = $oeuvreRepository->findBy([
            'type' => TypeOeuvre::PEINTURE
       ]);

        $sculptures = $oeuvreRepository->findBy([
           'type' => TypeOeuvre::SCULPTURE
       ]);

        $photos = $oeuvreRepository->findBy([
           'type' => TypeOeuvre::PHOTOGRAPHIE
        ]);
        $all = array_merge($peintures, $sculptures, $photos);
        return $this->render('oeuvre/oeuvres.html.twig', [
            'controller_name' => 'OeuvreController',
            'oeuvres' => $all,
            'peintures' => $peintures,
            'sculptures' => $sculptures,
            'photos' => $photos,
            'typeOeuvres' => TypeOeuvre::cases(),
            'collections' => $collectionsRepository->findAll(),
        ]);
    }
    

    #[Route('/test',name: 'app_oeuvre_index', methods: ['GET'])]
    public function index(OeuvreRepository $oeuvreRepository): Response
    {
        return $this->render('oeuvre/index.html.twig', [
            'oeuvres' => $oeuvreRepository->findAll(),
        ]);
    }


    /*#[Route('/new', name: 'app_oeuvre_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
    $oeuvre = new Oeuvre();
    $form = $this->createForm(OeuvreType::class, $oeuvre);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        /** @var UploadedFile|null $imageFile */
       /* $imageFile = $form->get('image')->getData();

        if ($imageFile) {
            // Read the binary content of the file
            $blobData = fopen($imageFile->getPathname(), 'rb');
            $oeuvre->setImage($blobData);
        }

        $entityManager->persist($oeuvre);
        $entityManager->flush();

        return $this->redirectToRoute('app_oeuvre_index');
    }

    return $this->render('oeuvre/new.html.twig', [
        'oeuvre' => $oeuvre,
        'form' => $form->createView(),
    ]);
   }*/


    #[Route('/test/{id}', name: 'app_oeuvre_show', methods: ['GET'])]
    public function show(Oeuvre $oeuvre): Response
    {
    $imageBase64 = null;
    $mimeType = null;

    if ($oeuvre->getImage()) {
        $imageData = $oeuvre->getImage();

        // Si c'est un flux
        if (is_resource($imageData)) {
            $imageData = stream_get_contents($imageData);
            fclose($oeuvre->getImage()); // fermer le flux
        }

        $imageBase64 = base64_encode($imageData);

        // Détecter le type MIME avec finfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData); // ex: image/jpeg, image/png
    }

    return $this->render('oeuvre/show.html.twig', [
        'oeuvre' => $oeuvre,
        'imageBase64' => $imageBase64,
        'mimeType' => $mimeType,
    ]);
    }

    #[Route('/{id}', name: 'app_oeuvre_details', methods: ['GET'])]
    public function details(Oeuvre $oeuvre): Response
    {
    $imageBase64 = null;
    $mimeType = null;

    if ($oeuvre->getImage()) {
        $imageData = $oeuvre->getImage();

        // Si c'est un flux
        if (is_resource($imageData)) {
            $imageData = stream_get_contents($imageData);
            fclose($oeuvre->getImage()); // fermer le flux
        }

        $imageBase64 = base64_encode($imageData);

        // Détecter le type MIME avec finfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData); // ex: image/jpeg, image/png
    }

    return $this->render('oeuvre/oeuvre_details.html.twig', [
        'oeuvre' => $oeuvre,
        'imageBase64' => $imageBase64,
        'mimeType' => $mimeType,
    ]);
    }


    #[Route('/{id}/edit', name: 'app_oeuvre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Oeuvre $oeuvre, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(OeuvreType::class, $oeuvre, [
            'user' => $user instanceof User ? $user : null,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image file upload during edit
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                // Read the binary content of the file
                $blobData = fopen($imageFile->getPathname(), 'rb');
                $oeuvre->setImage($blobData);
            }

            $entityManager->flush();

            // Return JSON for AJAX requests
            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => true, 'message' => 'Œuvre mise à jour avec succès']);
            }

            return $this->redirectToRoute('app_oeuvre_index', [], Response::HTTP_SEE_OTHER);
        }

        // For AJAX requests, return the form fields as HTML
        if ($request->isXmlHttpRequest()) {
            return $this->render('oeuvre/_form_fields.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        return $this->render('oeuvre/edit.html.twig', [
            'oeuvre' => $oeuvre,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_oeuvre_delete', methods: ['POST'])]
    public function delete(Request $request, Oeuvre $oeuvre, EntityManagerInterface $entityManager): Response
    {
        $likes = $oeuvre->getLikes(); 
        foreach ($likes as $like) {$entityManager->remove($like);}
        if ($this->isCsrfTokenValid('delete'.$oeuvre->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($oeuvre);
            $entityManager->flush();
        }

        return $this->redirectToRoute('oeuvres', [], Response::HTTP_SEE_OTHER);
    }
    

    
}
