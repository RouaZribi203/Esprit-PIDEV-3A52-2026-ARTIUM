<?php

namespace App\Controller;

use App\Entity\Musique;
use App\Enum\TypeOeuvre;
use App\Form\MusiqueType;
use App\Repository\CollectionsRepository;
use App\Repository\MusiqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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
        $artistCollections = $collectionsRepository->findBy([], ['titre' => 'ASC']);

        // Create new Musique entity
        $musique = new Musique();
        
        // Create the form
        $form = $this->createForm(MusiqueType::class, $musique, [
            'collection_choices' => $artistCollections,
        ]);
        $form->handleRequest($request);

        if (empty($artistCollections)) {
            $this->addFlash('error', 'Aucune collection disponible. Créez d\'abord une collection.');
        }
        
        // Handle form submission
        if ($form->isSubmitted()) {
            // First check if form is valid
            if (!$form->isValid()) {
                // Collect all validation errors
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                
                // Add field-specific errors
                foreach ($form as $child) {
                    foreach ($child->getErrors() as $error) {
                        $errors[] = $error->getMessage();
                    }
                }
                
                if (!empty($errors)) {
                    $this->addFlash('error', 'Validation failed: ' . implode(', ', $errors));
                }
                
                // Fetch music to re-render the page with form errors
                $searchTerm = $request->query->get('search', '');
                $sortBy = $request->query->get('sort', 'date');
                $sortOrder = $request->query->get('order', 'DESC');
                
                if (!in_array($sortOrder, ['ASC', 'DESC'])) {
                    $sortOrder = 'DESC';
                }
                
                if ($searchTerm) {
                    $musiques = $musiqueRepository->searchAndFilter($searchTerm, $sortBy, $sortOrder);
                } else {
                    $musiques = $musiqueRepository->searchAndFilter(null, $sortBy, $sortOrder);
                }
                
                return $this->render('Front Office/musiqueartiste/musiqueartiste.html.twig', [
                    'controller_name' => 'MusiqueartisteController',
                    'musiques' => $musiques,
                    'form' => $form->createView(),
                    'searchTerm' => $searchTerm,
                    'sortBy' => $sortBy,
                    'sortOrder' => $sortOrder,
                ]);
            }
            
            // Form is valid, proceed with file upload
            try {
                // Handle image upload
                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile) {
                    // Additional size check
                    if ($imageFile->getSize() > 5242880) { // 5MB
                        throw new \Exception('Image file exceeds maximum size of 5MB');
                    }
                    
                    $imageContent = file_get_contents($imageFile->getPathname());
                    if ($imageContent === false) {
                        throw new \Exception('Failed to read image file');
                    }
                    $musique->setImage($imageContent);
                }
                
                // Handle audio upload
                $audioFile = $form->get('audioFile')->getData();
                if ($audioFile) {
                    // Additional size check
                    if ($audioFile->getSize() > 20971520) { // 20MB
                        throw new \Exception('Audio file exceeds maximum size of 20MB');
                    }

                    $musique->setAudioFile($audioFile);
                }
                
                // Validate required fields are not empty after file processing
                if (!$musique->getTitre() || trim($musique->getTitre()) === '') {
                    throw new \Exception('Title cannot be empty');
                }
                if (!$musique->getDescription() || trim($musique->getDescription()) === '') {
                    throw new \Exception('Description cannot be empty');
                }
                if (!$musique->getGenre()) {
                    throw new \Exception('Genre must be selected');
                }
                if (!$audioFile) {
                    throw new \Exception('Audio file is required');
                }
                
                // Set creation date
                $musique->setDateCreation(new \DateTime());
                
                // Set type to MUSIQUE
                $musique->setType(TypeOeuvre::MUSIQUE);
                
                // Validate selected collection belongs to current artist
                $selectedCollection = $musique->getCollection();
                if (!$selectedCollection) {
                    throw new \Exception('Please select a collection.');
                }

                if (!$selectedCollection->getId()) {
                    throw new \Exception('Invalid collection selected.');
                }
                
                // Save to database
                $entityManager->persist($musique);
                $entityManager->flush();
                
                // Add success flash message
                $this->addFlash('success', 'Music added successfully!');
                
                // Redirect to avoid form resubmission
                return $this->redirectToRoute('app_musiqueartiste');
                
            } catch (\Exception $e) {
                // Handle database errors and file errors
                $errorMsg = 'An error occurred while uploading music.';
                
                if (strpos($e->getMessage(), 'server has gone away') !== false ||
                    strpos($e->getMessage(), 'max_allowed_packet') !== false) {
                    $errorMsg = 'The audio file is too large. Maximum allowed: 20MB';
                } elseif (strpos($e->getMessage(), 'Failed to read') !== false) {
                    $errorMsg = 'Failed to read file. Please try again.';
                } elseif (strpos($e->getMessage(), 'exceeds maximum') !== false) {
                    $errorMsg = $e->getMessage();
                } else {
                    $errorMsg = $e->getMessage();
                }
                
                $this->addFlash('error', $errorMsg);
            }
        }
        
        // Fetch all music pieces WITHOUT loading BLOB data
        // This prevents "MySQL server has gone away" errors with large audio files
        $searchTerm = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'date');
        $sortOrder = $request->query->get('order', 'DESC');
        
        // Validate sort order
        if (!in_array($sortOrder, ['ASC', 'DESC'])) {
            $sortOrder = 'DESC';
        }
        
        // Use search/filter method or fallback to all
        if ($searchTerm) {
            $musiques = $musiqueRepository->searchAndFilter($searchTerm, $sortBy, $sortOrder);
        } else {
            $musiques = $musiqueRepository->searchAndFilter(null, $sortBy, $sortOrder);
        }
        
        return $this->render('Front Office/musiqueartiste/musiqueartiste.html.twig', [
            'controller_name' => 'MusiqueartisteController',
            'musiques' => $musiques,
            'form' => $form->createView(),
            'searchTerm' => $searchTerm,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    #[Route('/musiqueartiste/audio/{id}', name: 'app_musiqueartiste_audio')]
    public function getAudio(int $id, MusiqueRepository $musiqueRepository): Response
    {
        $musique = $musiqueRepository->find($id);
        
        if (!$musique || !$musique->getAudio()) {
            throw $this->createNotFoundException('Audio not found');
        }

        $audioPath = $this->getParameter('kernel.project_dir') . '/public/uploads/music/' . $musique->getAudio();
        if (!is_file($audioPath)) {
            throw $this->createNotFoundException('Audio file not found on disk');
        }

        $response = new BinaryFileResponse($audioPath);
        $response->headers->set('Content-Type', mime_content_type($audioPath) ?: 'audio/mpeg');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, basename($audioPath));

        return $response;
    }

    #[Route('/musiqueartiste/image/{id}', name: 'app_musiqueartiste_image')]
    public function getImage(int $id, MusiqueRepository $musiqueRepository): Response
    {
        $musique = $musiqueRepository->find($id);
        
        if (!$musique || !$musique->getImage()) {
            throw $this->createNotFoundException('Image not found');
        }

        // Get image binary data from BLOB
        $imageData = $musique->getImage();
        if (is_resource($imageData)) {
            $imageData = stream_get_contents($imageData);
        }

        return new Response(
            $imageData,
            200,
            ['Content-Type' => 'image/jpeg']
        );
    }

    #[Route('/musiqueartiste/lyrics/{id}', name: 'app_musiqueartiste_lyrics', methods: ['GET'])]
    public function getLyrics(
        int $id,
        MusiqueRepository $musiqueRepository,
        HttpClientInterface $httpClient
    ): JsonResponse
    {
        $musique = $musiqueRepository->find($id);

        if (!$musique) {
            return $this->json([
                'success' => false,
                'message' => 'Song not found.'
            ], 404);
        }

        $trackName = trim((string) $musique->getTitre());
        $nom = trim((string) ($musique->getCollection()?->getArtiste()?->getNom() ?? ''));
        $prenom = trim((string) ($musique->getCollection()?->getArtiste()?->getPrenom() ?? ''));
        $artistName = trim($prenom . ' ' . $nom);

        if ($trackName === '') {
            return $this->json([
                'success' => false,
                'message' => 'Song title is missing.'
            ], 400);
        }

        $lyrics = null;
        $source = null;

        // Try lyrics.ovh first (simpler, more reliable API)
        if ($artistName !== '') {
            try {
                $lyricsOvhResponse = $httpClient->request('GET', sprintf(
                    'https://api.lyrics.ovh/v1/%s/%s',
                    rawurlencode($artistName),
                    rawurlencode($trackName)
                ), [
                    'timeout' => 10,
                    'max_duration' => 15,
                ]);

                if ($lyricsOvhResponse->getStatusCode() === 200) {
                    $lyricsOvhPayload = json_decode($lyricsOvhResponse->getContent(false), true);
                    if (is_array($lyricsOvhPayload) && !empty($lyricsOvhPayload['lyrics'])) {
                        $lyrics = trim($lyricsOvhPayload['lyrics']);
                        $source = 'lyrics.ovh';
                    }
                }
            } catch (\Throwable $e) {
                error_log('lyrics.ovh failed: ' . $e->getMessage());
            }
        }

        // Fallback to lrclib if lyrics.ovh didn't work
        if (!$lyrics) {
            try {
                $getParams = ['track_name' => $trackName];
                if ($artistName !== '') {
                    $getParams['artist_name'] = $artistName;
                }

                $getResponse = $httpClient->request('GET', 'https://lrclib.net/api/get', [
                    'query' => $getParams,
                    'timeout' => 10,
                    'max_duration' => 15,
                ]);

                if ($getResponse->getStatusCode() === 200) {
                    $payload = json_decode($getResponse->getContent(false), true);
                    if (is_array($payload)) {
                        $lyrics = $payload['plainLyrics'] ?? $payload['syncedLyrics'] ?? null;
                        if ($lyrics) {
                            $source = 'lrclib';
                        }
                    }
                }
            } catch (\Throwable $e) {
                error_log('lrclib.net /get failed: ' . $e->getMessage());
            }
        }

        if (!$lyrics) {
            return $this->json([
                'success' => false,
                'message' => 'Pas de paroles trouvées pour cette chanson.'
            ], 404);
        }

        return $this->json([
            'success' => true,
            'track' => $trackName,
            'artist' => $artistName,
            'lyrics' => $lyrics,
            'source' => $source
        ]);
    }

    #[Route('/musiqueartiste/edit/{id}', name: 'app_musiqueartiste_edit', methods: ['POST'])]
    public function edit(
        int $id,
        Request $request,
        MusiqueRepository $musiqueRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $musique = $musiqueRepository->find($id);
        
        if (!$musique) {
            $this->addFlash('error', 'Music not found.');
            return $this->redirectToRoute('app_musiqueartiste');
        }

        // Get and validate basic fields from POST data
        $titre = $request->request->get('titre');
        $description = $request->request->get('description');
        $genre = $request->request->get('genre');
        
        // Validation: Title
        if (empty($titre) || trim($titre) === '') {
            $this->addFlash('error', 'Title is required.');
            return $this->redirectToRoute('app_musiqueartiste');
        }
        
        if (strlen($titre) < 3) {
            $this->addFlash('error', 'Title must be at least 3 characters long.');
            return $this->redirectToRoute('app_musiqueartiste');
        }
        
        if (strlen($titre) > 255) {
            $this->addFlash('error', 'Title cannot exceed 255 characters.');
            return $this->redirectToRoute('app_musiqueartiste');
        }
        
        // Validation: Description
        if (empty($description) || trim($description) === '') {
            $this->addFlash('error', 'Description is required.');
            return $this->redirectToRoute('app_musiqueartiste');
        }
        
        if (strlen($description) < 10) {
            $this->addFlash('error', 'Description must be at least 10 characters long.');
            return $this->redirectToRoute('app_musiqueartiste');
        }
        
        if (strlen($description) > 5000) {
            $this->addFlash('error', 'Description cannot exceed 5000 characters.');
            return $this->redirectToRoute('app_musiqueartiste');
        }
        
        // Validation: Genre
        if (empty($genre)) {
            $this->addFlash('error', 'Genre is required.');
            return $this->redirectToRoute('app_musiqueartiste');
        }
        
        try {
            // Update fields
            $musique->setTitre(trim($titre));
            $musique->setDescription(trim($description));
            
            // Convert genre string to enum
            $musique->setGenre(\App\Enum\GenreMusique::from($genre));
        } catch (\ValueError $e) {
            $this->addFlash('error', 'Invalid genre selected.');
            return $this->redirectToRoute('app_musiqueartiste');
        }
        
        // Handle image upload (optional)
        $imageFile = $request->files->get('imageFile');
        if ($imageFile) {
            try {
                // Validate file size (5MB)
                if ($imageFile->getSize() > 5242880) {
                    throw new \Exception('Image file exceeds maximum size of 5MB');
                }
                
                // Validate MIME type
                $validMimes = ['image/jpeg', 'image/png', 'image/jpg'];
                if (!in_array($imageFile->getMimeType(), $validMimes)) {
                    throw new \Exception('Invalid image format. Please upload JPEG or PNG.');
                }
                
                // Validate image dimensions
                $imageInfo = @getimagesize($imageFile->getRealPath());
                if ($imageInfo === false) {
                    throw new \Exception('Unable to read image dimensions. File may be corrupted.');
                }
                
                $width = $imageInfo[0];
                $height = $imageInfo[1];
                
                if ($width < 300 || $height < 300) {
                    throw new \Exception(sprintf(
                        'Image dimensions (%dx%dpx) are too small. Minimum: 300x300px',
                        $width,
                        $height
                    ));
                }
                
                if ($width > 5000 || $height > 5000) {
                    throw new \Exception(sprintf(
                        'Image dimensions (%dx%dpx) are too large. Maximum: 5000x5000px',
                        $width,
                        $height
                    ));
                }
                
                $imageContent = file_get_contents($imageFile->getPathname());
                if ($imageContent === false) {
                    throw new \Exception('Failed to read image file');
                }
                
                $musique->setImage($imageContent);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error uploading image: ' . $e->getMessage());
                return $this->redirectToRoute('app_musiqueartiste');
            }
        }
        
        try {
            $entityManager->flush();
            $this->addFlash('success', 'Music updated successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while updating: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_musiqueartiste');
    }

    #[Route('/musiqueartiste/delete/{id}', name: 'app_musiqueartiste_delete', methods: ['POST'])]
    public function delete(
        int $id, 
        Request $request,
        MusiqueRepository $musiqueRepository, 
        EntityManagerInterface $entityManager
    ): Response
    {
        // Check if this is an AJAX request
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';
        
        // Validate deletion confirmation
        $confirmDelete = $request->request->get('confirm_delete');
        
        if (!$confirmDelete || $confirmDelete !== '1') {
            if ($isAjax) {
                return $this->json(['success' => false, 'message' => 'Delete action must be confirmed.'], 400);
            }
            $this->addFlash('error', 'Delete action must be confirmed.');
            return $this->redirectToRoute('app_musiqueartiste');
        }
        
        $musique = $musiqueRepository->find($id);
        
        if (!$musique) {
            if ($isAjax) {
                return $this->json(['success' => false, 'message' => 'Music not found.'], 404);
            }
            $this->addFlash('error', 'Music not found.');
            return $this->redirectToRoute('app_musiqueartiste');
        }
        
        // Store title for success message
        $musicTitle = $musique->getTitre();

        try {
            $entityManager->remove($musique);
            $entityManager->flush();
            
            if ($isAjax) {
                return $this->json([
                    'success' => true, 
                    'message' => "Music '{$musicTitle}' deleted successfully!"
                ], 200);
            }
            
            $this->addFlash('success', "Music '{$musicTitle}' deleted successfully!");
        } catch (\Exception $e) {
            if ($isAjax) {
                return $this->json([
                    'success' => false, 
                    'message' => 'Error deleting music: ' . $e->getMessage()
                ], 500);
            }
            $this->addFlash('error', 'Error deleting music: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_musiqueartiste');
    }
}
