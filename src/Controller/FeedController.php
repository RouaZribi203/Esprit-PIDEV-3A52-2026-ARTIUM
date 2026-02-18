<?php

namespace App\Controller;


use App\Entity\User;
use App\Entity\Commentaire;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Collections;
use App\Entity\Oeuvre;
use App\Enum\TypeOeuvre;
use App\Form\OeuvreType;
use App\Repository\CollectionsRepository;
use App\Repository\OeuvreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class FeedController extends AbstractController
{
    #[Route('/feed', name: 'app_feed')]
    public function index(OeuvreRepository $oeuvreRepository, CollectionsRepository $collectionsRepository): Response
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

        return $this->render('Front Office/feed/feed.html.twig', [
            'controller_name' => 'FeedController',
            'currentUser' => $currentUser,
            'oeuvres' => $all,
            'typeOeuvres' => TypeOeuvre::cases(),
            'collections' => $collectionsRepository->findAll(),
        ]);
    }
    #[Route('/feed_peintures', name: 'app_feed_peintures')]
    public function indexPeintures(OeuvreRepository $oeuvreRepository, CollectionsRepository $collectionsRepository): Response
    {
        $peintures = $oeuvreRepository->findBy([
            'type' => TypeOeuvre::PEINTURE
       ]);

        return $this->render('Front Office/feed/feed.html.twig', [
            'controller_name' => 'FeedController',
            'oeuvres' => $peintures,
            'typeOeuvres' => TypeOeuvre::cases(),
            'collections' => $collectionsRepository->findAll(),
        ]);
    }

    #[Route('/feed_sculptures', name: 'app_feed_sculptures')]
    public function indexSculptures(OeuvreRepository $oeuvreRepository, CollectionsRepository $collectionsRepository): Response
    {     
         
        $sculptures = $oeuvreRepository->findBy([
            'type' => TypeOeuvre::SCULPTURE
       ]);

        return $this->render('Front Office/feed/feed.html.twig', [
            'controller_name' => 'FeedController',
            'oeuvres' => $sculptures,
            'typeOeuvres' => TypeOeuvre::cases(),
            'collections' => $collectionsRepository->findAll(),
        ]);
    }
    #[Route('/feed_photos', name: 'app_feed_photos')]
    public function indexPhotos(OeuvreRepository $oeuvreRepository, CollectionsRepository $collectionsRepository): Response
    {
        $photos = $oeuvreRepository->findBy([
            'type' => TypeOeuvre::PHOTOGRAPHIE
       ]);

        return $this->render('Front Office/feed/feed.html.twig', [
            'controller_name' => 'FeedController',
            'oeuvres' => $photos,
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
    public function addCommentaire(Oeuvre $oeuvre,Request $request,EntityManagerInterface $em,UserRepository $userRepository): Response
    {
    $contenu = $request->request->get('contenu'); // récupère le texte du textarea
    if (!$contenu) {
        return $this->redirectToRoute('app_feed'); // si vide, on ne fait rien
    }

    $user = $this->getUser();
    if (!$user) {
        throw $this->createNotFoundException('User not found');
    }

    $commentaire = new Commentaire();
    $commentaire->setTexte($contenu);
    $commentaire->setUser($user);
    $commentaire->setOeuvre($oeuvre);
    $commentaire->setDateCommentaire(new \DateTime());
    $em->persist($commentaire);
    $em->flush();

    return $this->redirectToRoute('app_feed'); // retourne sur le feed après publication
}

    #[Route('/commentaire/{id}/delete', name: 'commentaire_delete', methods: ['POST'])]
    public function deleteCommentaire(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_feed');
        }

        // Fetch only the data we need without loading full entities
        $commentData = $em->createQuery(
            'SELECT c.id, IDENTITY(c.user) as userId, IDENTITY(c.oeuvre) as oeuvreId 
             FROM App\Entity\Commentaire c 
             WHERE c.id = :id'
        )
        ->setParameter('id', $id)
        ->getOneOrNullResult();

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

        // Delete using DQL to avoid entity loading
        $em->createQuery('DELETE FROM App\Entity\Commentaire c WHERE c.id = :id')
            ->setParameter('id', $id)
            ->execute();

        $redirectPath = (string) $request->request->get('redirect_path', '/feed');
        if ($redirectPath === '' || !str_starts_with($redirectPath, '/')) {
            $redirectPath = '/feed';
        }

        $anchor = $oeuvreId ? '#oeuvre-'.$oeuvreId : '';

        return $this->redirect($redirectPath.$anchor);
    }

    #[Route('/commentaire/{id}/edit', name: 'commentaire_edit', methods: ['POST'])]
    public function editCommentaire(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_feed');
        }

        $commentRows = $em->createQuery(
            'SELECT IDENTITY(c.user) as userId, IDENTITY(c.oeuvre) as oeuvreId
             FROM App\Entity\Commentaire c
             WHERE c.id = :id'
        )
        ->setParameter('id', $id)
        ->setMaxResults(1)
        ->getArrayResult();

        $commentData = $commentRows[0] ?? null;

        if (!$commentData) {
            return $this->redirectToRoute('app_feed');
        }

        if ((int) $commentData['userId'] !== $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier ce commentaire.');
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

        $em->createQuery('UPDATE App\Entity\Commentaire c SET c.texte = :texte WHERE c.id = :id')
            ->setParameter('texte', $contenu)
            ->setParameter('id', $id)
            ->execute();

        $redirectPath = (string) $request->request->get('redirect_path', '/feed');
        if ($redirectPath === '' || !str_starts_with($redirectPath, '/')) {
            $redirectPath = '/feed';
        }

        $anchor = !empty($commentData['oeuvreId']) ? '#oeuvre-'.$commentData['oeuvreId'] : '';

        return $this->redirect($redirectPath.$anchor);
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
