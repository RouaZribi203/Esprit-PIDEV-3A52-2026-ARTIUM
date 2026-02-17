<?php

namespace App\Controller;
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
    $processedOeuvres = [];
    foreach ($favoriteOeuvres as $oeuvre) {
        $image = $oeuvre->getImage();
        if ($image) {
            if (is_resource($image)) {
                rewind($image);
                $imageData = stream_get_contents($image);
            } else {
                $imageData = $image;
            }

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
        'processedOeuvres' => $processedOeuvres,
    ]);
    }

}
