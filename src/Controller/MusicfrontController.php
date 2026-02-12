<?php

namespace App\Controller;

use App\Entity\Playlist;
use App\Repository\MusiqueRepository;
use App\Repository\PlaylistRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MusicfrontController extends AbstractController
{
    #[Route('/user-musiques', name: 'app_musicfront')]
    public function index(
        Request $request,
        MusiqueRepository $musiqueRepository,
        PlaylistRepository $playlistRepository
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
        MusiqueRepository $musiqueRepository,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            $user = $userRepository->find(2);
        }
        if (!$user) {
            $this->addFlash('error', 'Utilisateur par defaut introuvable.');
            return $this->redirectToRoute('app_musicfront');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('create_playlist', $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez reessayer.');
            return $this->redirectToRoute('app_musicfront');
        }

        $name = trim((string) $request->request->get('playlist_name', ''));
        $description = trim((string) $request->request->get('playlist_description', ''));
        $ids = $request->request->all('musique_ids');

        if ($name === '') {
            $this->addFlash('error', 'Le nom de la playlist est obligatoire.');
            return $this->redirectToRoute('app_musicfront');
        }

        if (!is_array($ids)) {
            $ids = [];
        }

        $musiques = $ids ? $musiqueRepository->findBy(['id' => $ids]) : [];

        $playlist = new Playlist();
        $playlist->setNom($name);
        $playlist->setDescription($description !== '' ? $description : null);
        $playlist->setDateCreation(new \DateTime());
        $playlist->setUser($user);

        foreach ($musiques as $musique) {
            $playlist->addMusique($musique);
        }

        $entityManager->persist($playlist);
        $entityManager->flush();

        $this->addFlash('success', 'Playlist creee avec succes.');
        return $this->redirectToRoute('app_musicfront');
    }

    #[Route('/user-playlists/add-song', name: 'app_playlist_add_song', methods: ['POST'])]
    public function addSongToPlaylist(
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
            $this->addFlash('error', 'Utilisateur par defaut introuvable.');
            return $this->redirectToRoute('app_musicfront');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('add_to_playlist', $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez reessayer.');
            return $this->redirectToRoute('app_musicfront');
        }

        $playlistId = (int) $request->request->get('playlist_id');
        $musiqueId = (int) $request->request->get('musique_id');

        if ($playlistId <= 0 || $musiqueId <= 0) {
            $this->addFlash('error', 'Veuillez selectionner une playlist et une musique.');
            return $this->redirectToRoute('app_musicfront');
        }

        $playlist = $playlistRepository->find($playlistId);
        if (!$playlist || $playlist->getUser() !== $user) {
            $this->addFlash('error', 'Playlist introuvable.');
            return $this->redirectToRoute('app_musicfront');
        }

        $musique = $musiqueRepository->find($musiqueId);
        if (!$musique) {
            $this->addFlash('error', 'Musique introuvable.');
            return $this->redirectToRoute('app_musicfront');
        }

        if (!$playlist->getMusique()->contains($musique)) {
            $playlist->addMusique($musique);
            $entityManager->flush();
        }

        $this->addFlash('success', 'Musique ajoutee a la playlist.');
        return $this->redirectToRoute('app_musicfront');
    }
}
