<?php

namespace App\Controller;


use App\Entity\User;
use App\Entity\Commentaire;
use App\Service\RecommendationService;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Collections;
use App\Entity\Oeuvre;
use App\Enum\TypeOeuvre;
use App\Form\OeuvreType;
use App\Repository\CommentaireRepository;
use App\Repository\CollectionsRepository;
use App\Repository\OeuvreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class FeedController extends AbstractController
{
    private function getInitialDisplayCount(array $oeuvres, Request $request): int
    {
        $defaultCount = 3;
        $focusOeuvreId = (int) $request->query->get('focus_oeuvre', 0);

        if ($focusOeuvreId <= 0) {
            return $defaultCount;
        }

        foreach ($oeuvres as $index => $oeuvre) {
            if ($oeuvre instanceof Oeuvre && $oeuvre->getId() === $focusOeuvreId) {
                return max($defaultCount, $index + 1);
            }
        }

        return $defaultCount;
    }

    private function buildRedirectWithFocus(string $redirectPath, int $oeuvreId): string
    {
        if ($redirectPath === '' || !str_starts_with($redirectPath, '/')) {
            $redirectPath = '/feed';
        }

        $parts = parse_url($redirectPath);
        $path = $parts['path'] ?? '/feed';
        $query = [];

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        if ($oeuvreId > 0) {
            $query['focus_oeuvre'] = $oeuvreId;
        }

        $queryString = http_build_query($query);
        $base = $path.($queryString !== '' ? '?'.$queryString : '');
        $anchor = $oeuvreId > 0 ? '#oeuvre-'.$oeuvreId : '';

        return $base.$anchor;
    }

    #[Route('/feed', name: 'app_feed')]
    public function index(OeuvreRepository $oeuvreRepository, CollectionsRepository $collectionsRepository, Request $request): Response
    {
        $currentUser = $this->getUser();
        
        $oeuvres = $oeuvreRepository->findAll();
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
        $initialDisplayCount = $this->getInitialDisplayCount($all, $request);

        return $this->render('Front Office/feed/feed.html.twig', [
            'controller_name' => 'FeedController',
            'currentUser' => $currentUser,
            'oeuvres' => $all,
            'initialDisplayCount' => $initialDisplayCount,
            'typeOeuvres' => TypeOeuvre::cases(),
            'collections' => $collectionsRepository->findAll(),
        ]);
    }

    #[Route('/feed_recommandations', name: 'app_feed_recommandations')]
    public function indexRecommandations(OeuvreRepository $oeuvreRepository, CollectionsRepository $collectionsRepository, Request $request, RecommendationService $recommendationService): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        #$recommendedOeuvres = $recommendationService->getRecommendedOeuvres($user);
        $recommendedOeuvres = $recommendationService->getRecommendedOeuvresHybrid($user);

        $initialDisplayCount = $this->getInitialDisplayCount($recommendedOeuvres, $request);

        return $this->render('Front Office/feed/feed.html.twig', [
            'controller_name' => 'FeedController',
            'oeuvres' => $recommendedOeuvres,
            'initialDisplayCount' => $initialDisplayCount,
            'typeOeuvres' => TypeOeuvre::cases(),
            'collections' => $collectionsRepository->findAll(),
        ]);
    }

    
    #[Route('/feed_peintures', name: 'app_feed_peintures')]
    public function indexPeintures(OeuvreRepository $oeuvreRepository, CollectionsRepository $collectionsRepository, Request $request): Response
    {
        $peintures = $oeuvreRepository->findBy([
            'type' => TypeOeuvre::PEINTURE
       ]);
        $initialDisplayCount = $this->getInitialDisplayCount($peintures, $request);

        return $this->render('Front Office/feed/feed.html.twig', [
            'controller_name' => 'FeedController',
            'oeuvres' => $peintures,
            'initialDisplayCount' => $initialDisplayCount,
            'typeOeuvres' => TypeOeuvre::cases(),
            'collections' => $collectionsRepository->findAll(),
        ]);
    }


    #[Route('/feed_sculptures', name: 'app_feed_sculptures')]
    public function indexSculptures(OeuvreRepository $oeuvreRepository, CollectionsRepository $collectionsRepository, Request $request): Response
    {     
         
        $sculptures = $oeuvreRepository->findBy([
            'type' => TypeOeuvre::SCULPTURE
       ]);
        $initialDisplayCount = $this->getInitialDisplayCount($sculptures, $request);

        return $this->render('Front Office/feed/feed.html.twig', [
            'controller_name' => 'FeedController',
            'oeuvres' => $sculptures,
            'initialDisplayCount' => $initialDisplayCount,
            'typeOeuvres' => TypeOeuvre::cases(),
            'collections' => $collectionsRepository->findAll(),
        ]);
    }
    #[Route('/feed_photos', name: 'app_feed_photos')]
    public function indexPhotos(OeuvreRepository $oeuvreRepository, CollectionsRepository $collectionsRepository, Request $request): Response
    {
        $photos = $oeuvreRepository->findBy([
            'type' => TypeOeuvre::PHOTOGRAPHIE
       ]);
        $initialDisplayCount = $this->getInitialDisplayCount($photos, $request);

        return $this->render('Front Office/feed/feed.html.twig', [
            'controller_name' => 'FeedController',
            'oeuvres' => $photos,
            'initialDisplayCount' => $initialDisplayCount,
            'typeOeuvres' => TypeOeuvre::cases(),
            'collections' => $collectionsRepository->findAll(),
        ]);
    }



    #[Route('/oeuvre/{id}/favorite', name: 'oeuvre_favorite')]
    public function favorite(Oeuvre $oeuvre, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
    $user = $this->getUser();
    if (!$user) {
        throw $this->createNotFoundException('User not found');
    }

    // Toggle favorite
    if ($user->getFavUser()->contains($oeuvre)) {
        $user->removeFavUser($oeuvre);
    } else {
        $user->addFavUser($oeuvre);
    }

    $em->persist($user);
    $em->flush();

    return $this->redirectToRoute('app_feed'); // or wherever you came from
    }

    #[Route('/oeuvre/{id}/favorite-ajax', name: 'oeuvre_favorite_ajax', methods: ['POST'])]
    public function favoriteAjax(Oeuvre $oeuvre, EntityManagerInterface $em, Request $request): Response
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->json(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'User not logged in'], 401);
        }

        // Toggle favorite
        $isFavorited = $user->getFavUser()->contains($oeuvre);
        if ($isFavorited) {
            $user->removeFavUser($oeuvre);
        } else {
            $user->addFavUser($oeuvre);
        }

        $em->persist($user);
        $em->flush();

        return $this->json([
            'success' => true,
            'favorited' => !$isFavorited,
            'favoriteCount' => $oeuvre->getUserFav()->count()
        ]);
    }

    #[Route('/oeuvre/{id}/like-ajax', name: 'oeuvre_like_ajax', methods: ['POST'])]
    public function likeAjax(Oeuvre $oeuvre, EntityManagerInterface $em, Request $request): Response
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->json(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'User not logged in'], 401);
        }

        // Check if user has already liked this oeuvre
        $existingLike = null;
        foreach ($oeuvre->getLikes() as $like) {
            if ($like->getUser() === $user) {
                $existingLike = $like;
                break;
            }
        }

        if ($existingLike) {
            // Unlike - remove the like
            $oeuvre->removeLike($existingLike);
            $em->remove($existingLike);
            $isLiked = false;
        } else {
            // Like - create new like
            $like = new \App\Entity\Like();
            $like->setUser($user);
            $like->setOeuvre($oeuvre);
            $like->setLiked(true);
            $oeuvre->addLike($like);  // Add to collection
            $em->persist($like);
            $isLiked = true;
        }

        $em->flush();
        
        // Refresh the entity to get updated collection count
        $em->refresh($oeuvre);

        return $this->json([
            'success' => true,
            'liked' => $isLiked,
            'likeCount' => $oeuvre->getLikes()->count()
        ]);
    }

    #[Route('/oeuvre/{id}/commentaire', name: 'oeuvre_commentaire', methods: ['POST'])]
    public function addCommentaire(Oeuvre $oeuvre, Request $request, EntityManagerInterface $em): Response
    {
        $contenu = trim((string) $request->request->get('contenu'));
        $isTurboFrame = $request->headers->has('Turbo-Frame');
        
        if ($contenu === '') {
            if ($isTurboFrame) {
                return $this->render('Front Office/feed/list.html.twig', [
                    'oeuvre' => $oeuvre
                ]);
            }
            return $this->redirect($this->buildRedirectWithFocus('/feed', $oeuvre->getId()));
        }

        $user = $this->getUser();
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $commentaire = new Commentaire();
        $commentaire->setTexte($contenu);
        $commentaire->setUser($user);
        $oeuvre->addCommentaire($commentaire);
        $commentaire->setDateCommentaire(new \DateTime());
        $em->persist($commentaire);
        $em->flush();

        if ($isTurboFrame) {
            return $this->render('Front Office/feed/list.html.twig', [
                'oeuvre' => $oeuvre
            ]);
        }

        return $this->redirect($this->buildRedirectWithFocus('/feed', $oeuvre->getId()));
    }

    #[Route('/commentaire/{id}/delete', name: 'commentaire_delete', methods: ['POST'])]
    public function deleteCommentaire(int $id, Request $request, CommentaireRepository $commentaireRepository, OeuvreRepository $oeuvreRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_feed');
        }

        $commentData = $commentaireRepository->findOwnershipDataById($id);

        if (!$commentData) {
            return $this->redirectToRoute('app_feed');
        }

        if ($commentData['userId'] !== $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer ce commentaire.');
            return $this->redirectToRoute('app_feed');
        }

        $submittedToken = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_comment_'.$id, $submittedToken)) {
            $this->addFlash('error', 'Requête invalide.');
            return $this->redirectToRoute('app_feed');
        }

        $oeuvreId = $commentData['oeuvreId'];

        $commentaireRepository->deleteById($id);

        if ($request->headers->has('Turbo-Frame')) {
            $oeuvre = $oeuvreRepository->find($oeuvreId);
            if ($oeuvre) {
                return $this->render('Front Office/feed/list.html.twig', [
                    'oeuvre' => $oeuvre
                ]);
            }
        }

        return $this->redirect($this->buildRedirectWithFocus('/feed', (int) $oeuvreId));
    }

    
    #[Route('/feed/commentaire/{id}/edit', name: 'commentaire_edit', methods: ['POST'])]
    public function editCommentaire(int $id, Request $request, CommentaireRepository $commentaireRepository, OeuvreRepository $oeuvreRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_feed');
        }

        $submittedToken = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('edit_comment_'.$id, $submittedToken)) {
            $this->addFlash('error', 'Requête invalide.');
            return $this->redirectToRoute('app_feed');
        }

        $contenu = trim((string) $request->request->get('contenu'));
        if ($contenu === '') {
            $this->addFlash('error', 'Le commentaire ne peut pas être vide.');
            return $this->redirectToRoute('app_feed');
        }

        $updatedRows = $commentaireRepository->updateTextIfOwnedByUser($id, $user->getId(), $contenu);

        if ($updatedRows === 0) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier ce commentaire.');
            return $this->redirectToRoute('app_feed');
        }

        $oeuvreId = (int) $request->request->get('oeuvre_id', 0);

        if ($request->headers->has('Turbo-Frame') && $oeuvreId > 0) {
            $oeuvre = $oeuvreRepository->find($oeuvreId);
            if ($oeuvre) {
                return $this->render('Front Office/feed/list.html.twig', [
                    'oeuvre' => $oeuvre
                ]);
            }
        }

        return $this->redirect($this->buildRedirectWithFocus('/feed', $oeuvreId));
    }

    #[Route('/oeuvre/{id}/image', name: 'oeuvre_image', methods: ['GET'])]
    public function oeuvreImage(Oeuvre $oeuvre): Response
    {
        $imageData = $oeuvre->getImage();

        if (!$imageData) {
            throw $this->createNotFoundException('Image not found');
        }

        if (is_resource($imageData)) {
            rewind($imageData);
            $imageData = stream_get_contents($imageData);
        }

        // Detect MIME type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData) ?: 'image/jpeg';

        return new Response(
            $imageData,
            200,
            ['Content-Type' => $mimeType]
        );
    }


    }
