<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Livre;
use App\Repository\CollectionsRepository;
use App\Repository\LivreRepository;
use App\Repository\UserRepository;
use App\Form\RentalFormType;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Enum\TypeOeuvre;
use App\Repository\LocationLivreRepository;
use App\Enum\EtatLocation;

final class BibliofrontController extends AbstractController
{
    #[Route('/user-bibliotheque', name: 'app_bibliofront')]
    public function index(Request $request, LivreRepository $livreRepository, LocationLivreRepository $locationLivreRepository): Response
    {
        // search parameters from query string
        $q = trim((string) $request->query->get('q', ''));
        $category = trim((string) $request->query->get('category', ''));

        $qb = $livreRepository->createQueryBuilder('l')
            ->leftJoin('l.collection', 'c')
            ->addSelect('c')
            ->orderBy('l.date_creation', 'DESC');

        if ($q !== '') {
            $like = '%' . str_replace('%', '\\%', $q) . '%';
            $qb->andWhere('l.titre LIKE :like OR l.categorie LIKE :like')
               ->setParameter('like', $like);
        }

        if ($category !== '') {
            $qb->andWhere('l.categorie = :cat')->setParameter('cat', $category);
        }

        $livres = $qb->getQuery()->getResult();

        $currentUser = $this->getUser();
        $statusMap = [];
        $activeDateMap = [];
        $expirationMap = [];
        $rentalDaysMap = [];
        $remainingDaysMap = [];
        foreach ($livres as $livre) {
            $status = 'available';
            foreach ($livre->getLocationLivres() as $loc) {
                $etat = $loc->getEtat();
                $etatVal = is_object($etat) && property_exists($etat, 'value') ? $etat->value : (string) $etat;
                if ($etatVal === 'Active') {
                    // determine whether the active rental belongs to the current user
                    $isOwner = false;
                    $locUser = method_exists($loc, 'getUser') ? $loc->getUser() : null;
                    $currentUserId = $currentUser && method_exists($currentUser, 'getId') ? $currentUser->getId() : null;
                    $locUserId = $locUser && method_exists($locUser, 'getId') ? $locUser->getId() : null;
                    if ($currentUserId !== null && $locUserId !== null && $currentUserId === $locUserId) {
                        $isOwner = true;
                    }
                    if ($isOwner) {
                        $status = 'rented_by_you';
                    } else {
                        $status = 'unavailable';
                    }
                    // compute start/expiration/remaining based on date_debut; assume default 7 days if no duration stored
                    try {
                        $start = $loc->getDateDebut();
                        if ($start instanceof \DateTime) {
                            $activeDateMap[$livre->getId()] = $start->format('Y-m-d H:i:s');
                            $days = 7;
                            $expiration = (clone $start)->modify('+' . $days . ' days');
                            $expirationMap[$livre->getId()] = $expiration->format('Y-m-d H:i:s');
                            $rentalDaysMap[$livre->getId()] = $days;
                            $now = new \DateTime();
                            $diff = $expiration->getTimestamp() - $now->getTimestamp();
                            $remaining = $diff > 0 ? (int) ceil($diff / (60*60*24)) : 0;
                            $remainingDaysMap[$livre->getId()] = $remaining;
                        }
                    } catch (\Throwable $e) {
                        // ignore
                    }
                    break;
                }
            }
            $statusMap[$livre->getId()] = $status;
        }

        // fetch distinct categories for filter select
        $catRows = $livreRepository->createQueryBuilder('lc')->select('DISTINCT lc.categorie')->where('lc.categorie IS NOT NULL')->orderBy('lc.categorie', 'ASC')->getQuery()->getScalarResult();
        $categories = array_map(function($r){ return $r['categorie']; }, $catRows ?: []);

        return $this->render('Front Office/bibliofront/bibliofront.html.twig', [
            'controller_name' => 'BibliofrontController',
            'livres' => $livres,
            'categories' => $categories,
            'search_q' => $q,
            'search_category' => $category,
            'livreStatus' => $statusMap,
            'livreActiveDate' => $activeDateMap,
            'livreExpirationDate' => $expirationMap,
            'livreRentalDays' => $rentalDaysMap,
            'livreRemainingDays' => $remainingDaysMap,
        ]);
    }


    #[Route('/user-bibliotheque/louer/{id}/form', name: 'app_biblio_rent_form', methods: ['GET','POST'])]
    public function rentForm(Livre $livre, Request $request): Response
    {
        $form = $this->createForm(RentalFormType::class);
        $form->handleRequest($request);

        if ($request->isMethod('POST')) {
            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $nombre = (int) ($data['nombre_jours'] ?? 1);
                $prix = $livre->getPrixLocation() ?? 0;
                return $this->render('Front Office/bibliofront/_rent_confirm.html.twig', [
                    'livre' => $livre,
                    'nombre_jours' => $nombre,
                    'prix_par_jour' => $prix,
                ]);
            }

            // invalid: re-render form with errors
            return $this->render('Front Office/bibliofront/_rent_form.html.twig', [
                'form' => $form->createView(),
                'livre' => $livre,
            ]);
        }
        return $this->render('Front Office/bibliofront/_rent_form.html.twig', [
            'form' => $form->createView(),
            'livre' => $livre,
         ]);

    }


    #[Route('/user-bibliotheque/louer/{id}/confirm', name: 'app_biblio_rent_confirm', methods: ['POST'])]
    public function rentConfirm(Livre $livre, Request $request, EntityManagerInterface $em, UserRepository $userRepository): JsonResponse
    {
        $nombre = (int) $request->request->get('nombre_jours', 0);

        // check for existing active rental
        foreach ($livre->getLocationLivres() as $loc) {
            $etat = $loc->getEtat();
            $etatVal = is_object($etat) && property_exists($etat, 'value') ? $etat->value : (string) $etat;
            if ($etatVal === 'Active') {
                return $this->json(['success' => false, 'message' => 'Livre déjà loué']);
            }
        }

        // create a new LocationLivre and persist
        $location = new \App\Entity\LocationLivre();
        $location->setDateDebut(new \DateTime());
        $location->setEtat(\App\Enum\EtatLocation::ACTIVE);

        $user = $this->getUser();
        if (!$user) {
            // fallback to test user id 1 if anonymous
            $user = $userRepository->find(1);
            if (!$user) {
                return $this->json(['success' => false, 'message' => 'Utilisateur introuvable']);
            }
        }

        $location->setUser($user);
        $location->setLivre($livre);

        // persist requested rental duration (nombre_de_jours)
        $location->setNombreDeJours(max(1, $nombre));

        $em->persist($location);
        $em->flush();

        $this->addFlash('success', 'Location confirmée.');

        $start = $location->getDateDebut();
        $expiration = (clone $start)->modify('+' . max(1, $nombre) . ' days');

        return $this->json([
            'success' => true,
            'nombre_jours' => $nombre,
            'start_date' => $start->format('Y-m-d H:i:s'),
            'expiration_date' => $expiration->format('Y-m-d H:i:s'),
        ]);
    } 
}  

