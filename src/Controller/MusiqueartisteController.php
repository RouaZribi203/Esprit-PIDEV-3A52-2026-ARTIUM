<?php

namespace App\Controller;

use App\Entity\Musique;
use App\Enum\TypeOeuvre;
use App\Form\MusiqueType;
use App\Repository\CollectionsRepository;
use App\Repository\MusiqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MusiqueartisteController extends AbstractController
{
    #[Route('/musiqueartiste', name: 'app_musiqueartiste')]
    public function index(
        Request $request, 
        MusiqueRepository $musiqueRepository, 
        CollectionsRepository $collectionsRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Create new Musique entity
        $musique = new Musique();
        
        // Create the form
        $form = $this->createForm(MusiqueType::class, $musique);
        $form->handleRequest($request);
        
        // Handle form submission
        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                // Show validation errors
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                if (!empty($errors)) {
                    $this->addFlash('error', 'Validation failed: ' . implode(', ', $errors));
                }
            } else {
                try {
                // Handle image upload
                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile) {
                    $imageContent = file_get_contents($imageFile->getPathname());
                    $musique->setImage($imageContent);
                }
                
                // Handle audio upload
                $audioFile = $form->get('audioFile')->getData();
                if ($audioFile) {
                    $audioContent = file_get_contents($audioFile->getPathname());
                    $musique->setAudio($audioContent);
                }
                
                // Set creation date
                $musique->setDateCreation(new \DateTime());
                
                // Set type to MUSIQUE
                $musique->setType(TypeOeuvre::MUSIQUE);
                
                // Get or create a default collection for this user
                // TODO: You might want to let users select/create collections
                $collection = $collectionsRepository->findOneBy([]) ?? null;
                if ($collection) {
                    $musique->setCollection($collection);
                }
                
                // Save to database
                $entityManager->persist($musique);
                $entityManager->flush();
                
                // Add success flash message
                $this->addFlash('success', 'Music added successfully!');
                
                // Redirect to avoid form resubmission
                return $this->redirectToRoute('app_musiqueartiste');
                
            } catch (\Exception $e) {
                // Handle database errors (like max_allowed_packet exceeded)
                if (strpos($e->getMessage(), 'server has gone away') !== false || 
                    strpos($e->getMessage(), 'max_allowed_packet') !== false) {
                    $this->addFlash('error', 'The audio file is too large. Please use a file smaller than 15MB or compress your audio.');
                } else {
                    $this->addFlash('error', 'An error occurred while uploading: ' . $e->getMessage());
                }
            }
            }
        }
        
        // Fetch all music pieces WITHOUT loading BLOB data
        // This prevents "MySQL server has gone away" errors with large audio files
        $musiques = $musiqueRepository->findAllLightweight();
        
        return $this->render('Front Office/musiqueartiste/musiqueartiste.html.twig', [
            'controller_name' => 'MusiqueartisteController',
            'musiques' => $musiques,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/musiqueartiste/audio/{id}', name: 'app_musiqueartiste_audio')]
    public function getAudio(int $id, MusiqueRepository $musiqueRepository): Response
    {
        $musique = $musiqueRepository->find($id);
        
        if (!$musique || !$musique->getAudio()) {
            throw $this->createNotFoundException('Audio not found');
        }

        // Detect MIME type from the binary data
        $audioData = stream_get_contents($musique->getAudio());
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($audioData) ?: 'audio/mpeg';

        return new Response(
            $audioData,
            200,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $musique->getTitre() . '"'
            ]
        );
    }

    #[Route('/musiqueartiste/image/{id}', name: 'app_musiqueartiste_image')]
    public function getImage(int $id, MusiqueRepository $musiqueRepository): Response
    {
        $musique = $musiqueRepository->find($id);
        
        if (!$musique || !$musique->getImage()) {
            throw $this->createNotFoundException('Image not found');
        }

        return new Response(
            stream_get_contents($musique->getImage()),
            200,
            ['Content-Type' => 'image/jpeg']
        );
    }

    #[Route('/musiqueartiste/delete/{id}', name: 'app_musiqueartiste_delete', methods: ['POST'])]
    public function delete(int $id, MusiqueRepository $musiqueRepository, EntityManagerInterface $entityManager): Response
    {
        $musique = $musiqueRepository->find($id);
        
        if (!$musique) {
            $this->addFlash('error', 'Music not found.');
            return $this->redirectToRoute('app_musiqueartiste');
        }

        try {
            $entityManager->remove($musique);
            $entityManager->flush();
            
            $this->addFlash('success', 'Music deleted successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting music: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_musiqueartiste');
    }
}
