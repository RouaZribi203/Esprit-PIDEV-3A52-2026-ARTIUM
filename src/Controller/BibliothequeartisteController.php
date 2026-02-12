<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\CollectionsRepository;
use App\Repository\LivreRepository;
use App\Repository\UserRepository;
use App\Repository\LocationLivreRepository;
use App\Entity\Livre;
use App\Enum\EtatLocation;

final class BibliothequeartisteController extends AbstractController
{
    #[Route('/artiste-bibliotheque', name: 'app_bibliothequeartiste')]
    public function index(CollectionsRepository $collectionsRepository, LivreRepository $livreRepository, UserRepository $userRepository, LocationLivreRepository $locationLivreRepository): Response
    {
        // TODO: Replace test artist with $this->getUser() when authentication module is merged
        $artist = $userRepository->find(1);

        $collections = [];
        $livres = [];
        if ($artist) {
            $collections = $collectionsRepository->findBy(['artiste' => $artist]);

            // find livres that belong to this artist via collection relation
            $qb = $livreRepository->createQueryBuilder('l')
                ->join('l.collection', 'c')
                ->andWhere('c.artiste = :artist')
                ->setParameter('artist', $artist)
                ->orderBy('l.id', 'DESC');

            $livres = $qb->getQuery()->getResult();

            // compute basic stats per livre: count and total revenue (approx)
            $livreStats = [];
            foreach ($livres as $l) {
                $count = $locationLivreRepository->count(['livre' => $l]);
                $prix = $l->getPrixLocation() ?? 0;
                $total = $count * $prix;

                // find active location if any
                $active = $locationLivreRepository->findOneBy(['livre' => $l, 'etat' => EtatLocation::ACTIVE]);

                $livreStats[$l->getId()] = [
                    'count' => $count,
                    'total' => $total,
                    'activeId' => $active ? $active->getId() : null,
                    'activeDate' => $active && $active->getDateDebut() ? $active->getDateDebut()->format('Y-m-d H:i:s') : null,
                ];
            }
        }

        return $this->render('Front Office/bibliothequeartiste/bibliothequeartiste.html.twig', [
            'controller_name' => 'BibliothequeartisteController',
            'collections' => $collections,
            'livres' => $livres,
            'livreStats' => $livreStats ?? [],
        ]);
    }
}
