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
        // Process oeuvre images
        $processedOeuvres = [];
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

        return $this->render('Front Office/feed/feed.html.twig', [
            'controller_name' => 'FeedController',
            'currentUser' => $currentUser,
            'oeuvres' => $all,
            'typeOeuvres' => TypeOeuvre::cases(),
            'collections' => $collectionsRepository->findAll(),
            'processedOeuvres' => $processedOeuvres,
        ]);
    }
    #[Route('/feed_peintures', name: 'app_feed_peintures')]
    public function indexPeintures(OeuvreRepository $oeuvreRepository, CollectionsRepository $collectionsRepository): Response
    {
        $peintures = $oeuvreRepository->findBy([
            'type' => TypeOeuvre::PEINTURE
       ]);
        $processedOeuvres = [];
        foreach ($peintures as $oeuvre) {
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

        return $this->render('Front Office/feed/feed.html.twig', [
            'controller_name' => 'FeedController',
            'oeuvres' => $peintures,
            'typeOeuvres' => TypeOeuvre::cases(),
            'collections' => $collectionsRepository->findAll(),
            'processedOeuvres' => $processedOeuvres,
        ]);
    }

    #[Route('/feed_sculptures', name: 'app_feed_sculptures')]
    public function indexSculptures(OeuvreRepository $oeuvreRepository, CollectionsRepository $collectionsRepository): Response
    {     
         
        $sculptures = $oeuvreRepository->findBy([
            'type' => TypeOeuvre::SCULPTURE
       ]);
        $processedOeuvres = [];
        foreach ($sculptures as $oeuvre) {
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

        return $this->render('Front Office/feed/feed.html.twig', [
            'controller_name' => 'FeedController',
            'oeuvres' => $sculptures,
            'typeOeuvres' => TypeOeuvre::cases(),
            'collections' => $collectionsRepository->findAll(),
            'processedOeuvres' => $processedOeuvres,
        ]);
    }
    #[Route('/feed_photos', name: 'app_feed_photos')]
    public function indexPhotos(OeuvreRepository $oeuvreRepository, CollectionsRepository $collectionsRepository): Response
    {
        $photos = $oeuvreRepository->findBy([
            'type' => TypeOeuvre::PHOTOGRAPHIE
       ]);
        $processedOeuvres = [];
        foreach ($photos as $oeuvre) {
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

        return $this->render('Front Office/feed/feed.html.twig', [
            'controller_name' => 'FeedController',
            'oeuvres' => $photos,
            'typeOeuvres' => TypeOeuvre::cases(),
            'collections' => $collectionsRepository->findAll(),
            'processedOeuvres' => $processedOeuvres,
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


    }
