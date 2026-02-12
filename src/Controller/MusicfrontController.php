<?php

namespace App\Controller;

use App\Entity\Playlist;
use App\Repository\MusiqueRepository;
use App\Repository\PlaylistRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MusicfrontController extends AbstractController
{
    #[Route('/user-musiques', name: 'app_musicfront')]
    public function index(
        Request $request,
        MusiqueRepository $musiqueRepository,
        PlaylistRepository $playlistRepository,
        UserRepository $userRepository
    ): Response
    {
        $searchTerm = trim((string) $request->query->get('search', ''));
        $sortBy = (string) $request->query->get('sort', 'date');
        $sortOrder = strtoupper((string) $request->query->get('order', 'DESC'));

        if (!in_array($sortOrder, ['ASC', 'DESC'], true)) {
            $sortOrder = 'DESC';
        }

        if (!in_array($sortBy, ['date', 'titre', 'genre'], true)) {
            $sortBy = 'date';
        }

        // Fetch all available songs
        $musiques = $musiqueRepository->findAll();

        if ($searchTerm !== '') {
            $musiques = array_values(array_filter($musiques, function ($musique) use ($searchTerm) {
                $titre = $musique->getTitre() ?? '';
                $description = $musique->getDescription() ?? '';
                return stripos($titre, $searchTerm) !== false
                    || stripos($description, $searchTerm) !== false;
            }));
        }

        usort($musiques, function ($a, $b) use ($sortBy, $sortOrder) {
            switch ($sortBy) {
                case 'titre':
                    $left = $a->getTitre() ?? '';
                    $right = $b->getTitre() ?? '';
                    $result = strcasecmp($left, $right);
                    break;
                case 'genre':
                    $left = $a->getGenre()?->value ?? '';
                    $right = $b->getGenre()?->value ?? '';
                    $result = strcasecmp($left, $right);
                    break;
                case 'date':
                default:
                    $left = $a->getDateCreation()?->getTimestamp() ?? 0;
                    $right = $b->getDateCreation()?->getTimestamp() ?? 0;
                    $result = $left <=> $right;
                    break;
            }

            return $sortOrder === 'DESC' ? -$result : $result;
        });

        $playlists = [];

        $user = $this->getUser();
        if ($user) {
            $playlists = $playlistRepository->findBy(
                ['user' => $user],
                ['date_creation' => 'DESC']
            );
        } else {
            // For guest users, fetch playlists for user ID 2
            $guestUser = $userRepository->find(2);
            if ($guestUser) {
                $playlists = $playlistRepository->findBy(
                    ['user' => $guestUser],
                    ['date_creation' => 'DESC']
                );
            }
        }
        
        return $this->render('Front Office/music/musicfront.html.twig', [
            'controller_name' => 'MusicfrontController',
            'musiques' => $musiques,
            'playlists' => $playlists,
            'searchTerm' => $searchTerm,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    #[Route('/user-playlists/create', name: 'app_playlist_create', methods: ['POST'])]
    public function createPlaylist(
        Request $request,
        PlaylistRepository $playlistRepository,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        \Symfony\Component\Validator\Validator\ValidatorInterface $validator
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            $user = $userRepository->find(2);
        }
        if (!$user) {
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return new JsonResponse(['error' => 'Utilisateur par defaut introuvable.'], 400);
            }
            $this->addFlash('error', 'Utilisateur par defaut introuvable.');
            return $this->redirectToRoute('app_musicfront');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('create_playlist', $token)) {
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return new JsonResponse(['error' => 'Jeton CSRF invalide.'], 400);
            }
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez reessayer.');
            return $this->redirectToRoute('app_musicfront');
        }

        $name = trim((string) $request->request->get('playlist_name', ''));
        $description = trim((string) $request->request->get('playlist_description', ''));

        $playlist = new Playlist();
        $playlist->setNom($name);
        $playlist->setDescription($description !== '' ? $description : null);
        $playlist->setDateCreation(new \DateTime());
        $playlist->setUser($user);

        // Validate entity
        $errors = $validator->validate($playlist);
        if (count($errors) > 0) {
            $errorMessage = $errors[0]->getMessage();
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return new JsonResponse(['error' => $errorMessage], 400);
            }
            $this->addFlash('error', $errorMessage);
            return $this->redirectToRoute('app_musicfront');
        }

        // Handle image upload
        $imageFile = $request->files->get('playlist_image');
        if ($imageFile && $imageFile->isValid()) {
            try {
                if ($imageFile->getSize() > 5242880) { // 5MB
                    throw new \Exception('Image file exceeds maximum size of 5MB');
                }
                
                // Validate image MIME type
                $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($imageFile->getMimeType(), $allowedMimes)) {
                    throw new \Exception('Invalid image format. Allowed formats: JPG, PNG, GIF, WebP');
                }
                
                $imageContent = file_get_contents($imageFile->getPathname());
                if ($imageContent === false) {
                    throw new \Exception('Failed to read image file');
                }
                $playlist->setImage($imageContent);
            } catch (\Exception $e) {
                if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                    return new JsonResponse(['error' => 'Image upload failed: ' . $e->getMessage()], 400);
                }
                $this->addFlash('warning', 'Image upload failed: ' . $e->getMessage());
            }
        }

        $entityManager->persist($playlist);
        $entityManager->flush();

        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return new JsonResponse(['success' => true]);
        }

        $this->addFlash('success', 'Playlist creee avec succes.');
        return $this->redirectToRoute('app_musicfront');
    }

    #[Route('/user-playlists/add-songs', name: 'app_playlist_add_multiple', methods: ['POST'])]
    public function addMultipleSongs(
        Request $request,
        PlaylistRepository $playlistRepository,
        MusiqueRepository $musiqueRepository,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            $user = $userRepository->find(2);
        }
        if (!$user) {
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return new JsonResponse(['error' => 'Utilisateur par defaut introuvable.'], 400);
            }
            $this->addFlash('error', 'Utilisateur par defaut introuvable.');
            return $this->redirectToRoute('app_musicfront');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('add_to_playlist', $token)) {
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return new JsonResponse(['error' => 'Jeton CSRF invalide.'], 400);
            }
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez reessayer.');
            return $this->redirectToRoute('app_musicfront');
        }

        $playlistId = (int) $request->request->get('playlist_id');
        $songIds = $request->request->all()['song_ids'] ?? [];

        if ($playlistId <= 0 || empty($songIds)) {
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return new JsonResponse(['error' => 'Veuillez selectionner une playlist et au moins une musique.'], 400);
            }
            $this->addFlash('error', 'Veuillez selectionner une playlist et au moins une musique.');
            return $this->redirectToRoute('app_musicfront');
        }

        $playlist = $playlistRepository->find($playlistId);
        if (!$playlist || $playlist->getUser() !== $user) {
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return new JsonResponse(['error' => 'Playlist introuvable.'], 404);
            }
            $this->addFlash('error', 'Playlist introuvable.');
            return $this->redirectToRoute('app_musicfront');
        }

        $addedCount = 0;
        foreach ($songIds as $musicId) {
            $musicId = (int) $musicId;
            if ($musicId <= 0) continue;

            $musique = $musiqueRepository->find($musicId);
            if (!$musique) continue;

            if (!$playlist->getMusique()->contains($musique)) {
                $playlist->addMusique($musique);
                $addedCount++;
            }
        }

        if ($addedCount > 0) {
            $entityManager->flush();
        }

        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return new JsonResponse([
                'success' => true,
                'message' => $addedCount . ' musique(s) ajoutee(s) a la playlist.'
            ]);
        }

        $this->addFlash('success', $addedCount . ' musique(s) ajoutee(s) a la playlist.');
        return $this->redirectToRoute('app_musicfront');
    }

    #[Route('/playlist/{id}/songs', name: 'app_playlist_songs', methods: ['GET'])]
    public function getPlaylistSongs(int $id, PlaylistRepository $playlistRepository): Response
    {
        $playlist = $playlistRepository->find($id);
        
        if (!$playlist) {
            throw $this->createNotFoundException('Playlist not found');
        }

        $songs = [];
        foreach ($playlist->getMusique() as $musique) {
            $songs[] = [
                'id' => $musique->getId(),
                'titre' => $musique->getTitre(),
                'audioSrc' => $this->generateUrl('app_musiqueartiste_audio', ['id' => $musique->getId()]),
            ];
        }

        return new JsonResponse(['songs' => $songs]);
    }

    #[Route('/playlist/{playlistId}/remove-song/{musicId}', name: 'app_playlist_remove_song', methods: ['POST'])]
    public function removeSongFromPlaylist(
        int $playlistId,
        int $musicId,
        Request $request,
        PlaylistRepository $playlistRepository,
        MusiqueRepository $musiqueRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('remove_song', $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_musicfront');
        }

        $playlist = $playlistRepository->find($playlistId);
        $musique = $musiqueRepository->find($musicId);

        if (!$playlist || !$musique) {
            $this->addFlash('error', 'Playlist ou musique introuvable.');
            return $this->redirectToRoute('app_musicfront');
        }

        if ($playlist->getMusique()->contains($musique)) {
            $playlist->removeMusique($musique);
            $entityManager->flush();
            $this->addFlash('success', 'Musique supprimee de la playlist.');
        }

        return $this->redirectToRoute('app_musicfront');
    }

    #[Route('/playlist/{id}/delete', name: 'app_playlist_delete', methods: ['POST'])]
    public function deletePlaylist(
        int $id,
        Request $request,
        PlaylistRepository $playlistRepository,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_playlist', $token)) {
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return new JsonResponse(['error' => 'Jeton CSRF invalide.'], 400);
            }
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_musicfront');
        }

        $playlist = $playlistRepository->find($id);
        
        if (!$playlist) {
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return new JsonResponse(['error' => 'Playlist introuvable.'], 404);
            }
            $this->addFlash('error', 'Playlist introuvable.');
            return $this->redirectToRoute('app_musicfront');
        }

        // Check if user owns this playlist
        $user = $this->getUser();
        if (!$user) {
            // For guest users, check if playlist belongs to user ID 2
            $guestUser = $userRepository->find(2);
            if (!$guestUser || $playlist->getUser()->getId() !== $guestUser->getId()) {
                if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                    return new JsonResponse(['error' => 'Vous netes pas autorise a supprimer cette playlist.'], 403);
                }
                $this->addFlash('error', 'Vous netes pas autorise a supprimer cette playlist.');
                return $this->redirectToRoute('app_musicfront');
            }
        } else {
            // For logged-in users, verify they own the playlist
            if ($playlist->getUser()->getId() !== $user->getId()) {
                if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                    return new JsonResponse(['error' => 'Vous netes pas autorise a supprimer cette playlist.'], 403);
                }
                $this->addFlash('error', 'Vous netes pas autorise a supprimer cette playlist.');
                return $this->redirectToRoute('app_musicfront');
            }
        }

        $playlistName = $playlist->getNom();
        $entityManager->remove($playlist);
        $entityManager->flush();

        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return new JsonResponse(['success' => true]);
        }

        $this->addFlash('success', 'La playlist « ' . $playlistName . ' » a ete supprimee.');
        return $this->redirectToRoute('app_musicfront');
    }

    #[Route('/playlist/{id}/edit', name: 'app_playlist_edit', methods: ['POST'])]
    public function editPlaylist(
        int $id,
        Request $request,
        PlaylistRepository $playlistRepository,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        \Symfony\Component\Validator\Validator\ValidatorInterface $validator
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('edit_playlist', $token)) {
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return new JsonResponse(['error' => 'Jeton CSRF invalide.'], 400);
            }
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_musicfront');
        }

        $playlist = $playlistRepository->find($id);
        
        if (!$playlist) {
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return new JsonResponse(['error' => 'Playlist introuvable.'], 404);
            }
            $this->addFlash('error', 'Playlist introuvable.');
            return $this->redirectToRoute('app_musicfront');
        }

        // Check if user owns this playlist
        $user = $this->getUser();
        if (!$user) {
            // For guest users, check if playlist belongs to user ID 2
            $guestUser = $userRepository->find(2);
            if (!$guestUser || $playlist->getUser()->getId() !== $guestUser->getId()) {
                if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                    return new JsonResponse(['error' => 'Vous netes pas autorise a modifier cette playlist.'], 403);
                }
                $this->addFlash('error', 'Vous netes pas autorise a modifier cette playlist.');
                return $this->redirectToRoute('app_musicfront');
            }
        } else {
            // For logged-in users, verify they own the playlist
            if ($playlist->getUser()->getId() !== $user->getId()) {
                if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                    return new JsonResponse(['error' => 'Vous netes pas autorise a modifier cette playlist.'], 403);
                }
                $this->addFlash('error', 'Vous netes pas autorise a modifier cette playlist.');
                return $this->redirectToRoute('app_musicfront');
            }
        }

        $nom = trim((string) $request->request->get('nom', ''));
        $description = trim((string) $request->request->get('description', ''));

        $playlist->setNom($nom);
        $playlist->setDescription($description !== '' ? $description : null);

        // Validate entity
        $errors = $validator->validate($playlist);
        if (count($errors) > 0) {
            $errorMessage = $errors[0]->getMessage();
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return new JsonResponse(['error' => $errorMessage], 400);
            }
            $this->addFlash('error', $errorMessage);
            return $this->redirectToRoute('app_musicfront');
        }

        $entityManager->flush();

        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return new JsonResponse(['success' => true]);
        }

        $this->addFlash('success', 'La playlist a ete modifiee avec succes.');
        return $this->redirectToRoute('app_musicfront');
    }

    #[Route('/playlist/image/{id}', name: 'app_playlist_image')]
    public function getPlaylistImage(int $id, PlaylistRepository $playlistRepository): Response
    {
        $playlist = $playlistRepository->find($id);
        
        if (!$playlist || !$playlist->getImage()) {
            throw $this->createNotFoundException('Playlist image not found');
        }

        // Get image binary data from BLOB
        $imageData = $playlist->getImage();
        if (is_resource($imageData)) {
            $imageData = stream_get_contents($imageData);
        }

        return new Response(
            $imageData,
            200,
            ['Content-Type' => 'image/jpeg']
        );
    }
}
