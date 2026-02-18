<?php

namespace App\Controller;
use App\Entity\Oeuvre;
use App\Repository\UserRepository; 
use App\Enum\TypeOeuvre;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FavorisController extends AbstractController
{
    /*#[Route('/favoris', name: 'app_favoris')]
    public function index(): Response
    {
        return $this->render('Front Office/favoris/favoris.html.twig', [
            'controller_name' => 'FavorisController',
        ]);
    }*/
    #[Route('/favoris', name: 'app_favoris')]
    public function userFavorites(UserRepository $userRepository): Response
    {
    $user = $this->getUser();

    if (!$user) {
        throw $this->createNotFoundException('User not found');
    }

    $favoriteOeuvres = $user->getFavUser()->toArray();

    $favoritesPeintures = [];
    $favoritesSculptures = [];
    $favoritesPhotographies = [];
    foreach ($favoriteOeuvres as $oeuvre) {
        $type = $oeuvre->getType();
        if ($type === TypeOeuvre::PEINTURE) {
            $favoritesPeintures[] = $oeuvre;
        } elseif ($type === TypeOeuvre::SCULPTURE) {
            $favoritesSculptures[] = $oeuvre;
        } elseif ($type === TypeOeuvre::PHOTOGRAPHIE) {
            $favoritesPhotographies[] = $oeuvre;
        }
    }

    return $this->render('Front Office/favoris/favoris.html.twig', [
        'user' => $user,
        'favorites' => $favoriteOeuvres,
        'favoritesPeintures' => $favoritesPeintures,
        'favoritesSculptures' => $favoritesSculptures,
        'favoritesPhotographies' => $favoritesPhotographies,
    ]);
    }

    #[Route('/favoris/oeuvre/{id}/image', name: 'favoris_oeuvre_image', methods: ['GET'])]
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

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData) ?: 'image/jpeg';

        return new Response(
            $imageData,
            200,
            ['Content-Type' => $mimeType]
        );
    }

}
