<?php

namespace App\Controller;

use App\Entity\Commentaire;
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
    public function indexx(OeuvreRepository $oeuvreRepository, CollectionsRepository $collectionsRepository, Request $request): Response
    {
        $query = $request->query->get('q', '');
        $sortBy = $request->query->get('sort', 'titre');
        $sortOrder = $request->query->get('order', 'ASC');
        $searchResults = [];
        $noResultsMessage = '';

        // If search query exists, find matching oeuvres with sorting
        if ($query) {
            $searchResults = $oeuvreRepository->findByTitreWithSort($query, $sortBy, $sortOrder);

            if (count($searchResults) === 1) {
                // Single result: redirect to details
                return $this->redirectToRoute('app_oeuvre_details', ['id' => $searchResults[0]->getId()]);
            } elseif (count($searchResults) === 0) {
                // No results
                $noResultsMessage = 'Aucune œuvre trouvée avec ce titre.';
                $searchResults = [];
            }
            // Multiple results: will be displayed in template
        }

        // Get oeuvres by type with sorting
        $peintures = $oeuvreRepository->findBy(
            ['type' => TypeOeuvre::PEINTURE]
        );

        $sculptures = $oeuvreRepository->findBy(
           ['type' => TypeOeuvre::SCULPTURE]
        );

        $photos = $oeuvreRepository->findBy(
           ['type' => TypeOeuvre::PHOTOGRAPHIE]
        );
        
        // Apply sorting to all oeuvres if needed
        $all = $oeuvreRepository->findAllWithSort($sortBy, $sortOrder);
        
        return $this->render('oeuvre/oeuvres.html.twig', [
            'controller_name' => 'OeuvreController',
            'oeuvres' => $all,
            'peintures' => $peintures,
            'sculptures' => $sculptures,
            'photos' => $photos,
            'typeOeuvres' => TypeOeuvre::cases(),
            'collections' => $collectionsRepository->findAll(),
            'searchResults' => $searchResults,
            'noResultsMessage' => $noResultsMessage,
            'isSearchActive' => (bool) $query,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'currentQuery' => $query,
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
            'validation_groups' => ['Default', 'edit'],
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
        $oeuvreId = $oeuvre->getId();
        
        if ($this->isCsrfTokenValid('delete'.$oeuvreId, $request->getPayload()->getString('_token'))) {
            // Delete using DQL to avoid entity loading and unbuffered query issues
            $entityManager->createQuery('DELETE FROM App\Entity\Like l WHERE l.oeuvre = :oeuvre')
                ->setParameter('oeuvre', $oeuvreId)
                ->execute();
            
            $entityManager->createQuery('DELETE FROM App\Entity\Commentaire c WHERE c.oeuvre = :oeuvre')
                ->setParameter('oeuvre', $oeuvreId)
                ->execute();
            
            $entityManager->remove($oeuvre);
            $entityManager->flush();
        }

        return $this->redirectToRoute('oeuvres', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/admin/commentaire/{id}/delete', name: 'app_oeuvre_comment_delete', methods: ['POST'])]
    public function deleteComment(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Fetch only the data we need without loading full entities
        $commentData = $entityManager->createQuery(
            'SELECT c.id, IDENTITY(c.oeuvre) as oeuvreId FROM App\Entity\Commentaire c WHERE c.id = :id'
        )
        ->setParameter('id', $id)
        ->getOneOrNullResult();

        if (!$commentData) {
            return $this->redirectToRoute('oeuvres');
        }

        $submittedToken = (string) $request->getPayload()->getString('_token');
        if (!$this->isCsrfTokenValid('delete_comment'.$id, $submittedToken)) {
            return $this->redirectToRoute('oeuvres');
        }

        $oeuvreId = $commentData['oeuvreId'];

        // Delete using DQL to avoid entity loading
        $entityManager->createQuery('DELETE FROM App\Entity\Commentaire c WHERE c.id = :id')
            ->setParameter('id', $id)
            ->execute();

        if ($oeuvreId !== null) {
            return $this->redirectToRoute('app_oeuvre_details', ['id' => $oeuvreId], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('oeuvres', [], Response::HTTP_SEE_OTHER);
    }
    

    
}
