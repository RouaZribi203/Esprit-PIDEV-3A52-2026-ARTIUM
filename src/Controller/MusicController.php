<?php

namespace App\Controller;

use App\Repository\MusiqueRepository;
use App\Repository\PlaylistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MusicController extends AbstractController
{
    #[Route('/musique', name: 'musiques')]
    public function index(Request $request, MusiqueRepository $musiqueRepository, PlaylistRepository $playlistRepository): Response
    {
        $view = $request->query->get('view', 'music');
        
        // Get search and sort parameters
        $searchTerm = $request->query->get('search');
        $sortBy = $request->query->get('sort', 'date');
        $sortOrder = $request->query->get('order', 'DESC');
        
        // Fetch music with search/sort
        $musiques = $musiqueRepository->searchAndFilter($searchTerm, $sortBy, $sortOrder);
        
        // Fetch playlists
        $playlists = $playlistRepository->findAll();
        
        return $this->render('music/music.html.twig', [
            'musiques' => $musiques,
            'playlists' => $playlists,
            'searchTerm' => $searchTerm,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'currentView' => $view,
        ]);
    }

    #[Route('/admin/playlist/{id}/delete', name: 'app_admin_playlist_delete', methods: ['POST'])]
    public function deletePlaylist(
        int $id,
        Request $request,
        PlaylistRepository $playlistRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Check AJAX request
        if ($request->headers->get('X-Requested-With') !== 'XMLHttpRequest') {
            return new JsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $playlist = $playlistRepository->find($id);
        
        if (!$playlist) {
            return new JsonResponse(['success' => false, 'message' => 'Playlist not found'], 404);
        }

        $playlistName = $playlist->getNom();
        
        try {
            $entityManager->remove($playlist);
            $entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => "Playlist '" . $playlistName . "' deleted successfully!"
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error deleting playlist: ' . $e->getMessage()
            ], 500);
        }
    }
}
