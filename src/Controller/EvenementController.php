<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/evenement')]
final class EvenementController extends AbstractController
{
    #[Route(name: 'evenements', methods: ['GET'])]
    public function index(
        Request $request,
        EvenementRepository $evenementRepository,
        TicketRepository $ticketRepository
    ): Response
    {
        // Pagination setup
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $filterValue = trim((string) $request->query->get('filter_value', ''));
        $sort = (string) $request->query->get('sort', 'titre_asc');
        $statusFilter = (string) $request->query->get('status', '');

        $allowedSorts = [
            'titre_asc' => ['e.titre', 'ASC'],
            'titre_desc' => ['e.titre', 'DESC'],
            'date_asc' => ['e.dateDebut', 'ASC'],
            'date_desc' => ['e.dateDebut', 'DESC'],
        ];
        if (!array_key_exists($sort, $allowedSorts)) {
            $sort = 'titre_asc';
        }

        $filterParams = array_filter([
            'filter_value' => $filterValue,
            'sort' => $sort,
            'status' => $statusFilter,
        ], static fn ($value) => $value !== '');
        
        // Build query with filters and pagination
        $queryBuilder = $evenementRepository->createQueryBuilder('e');

        [$sortField, $sortDirection] = $allowedSorts[$sort];
        $queryBuilder->orderBy($sortField, $sortDirection);

        if ($filterValue !== '') {
            $queryBuilder
                ->andWhere('LOWER(e.titre) LIKE :filterTitre')
                ->setParameter('filterTitre', '%' . mb_strtolower($filterValue) . '%');
        }

        if ($statusFilter !== '') {
            $statutMap = [
                'A_VENIR' => 'À venir',
                'TERMINE' => 'Terminé',
                'ANNULE' => 'Annulé',
            ];
            $statutKey = mb_strtoupper($statusFilter);
            if (isset($statutMap[$statutKey])) {
                $queryBuilder
                    ->andWhere('e.statut = :filterStatut')
                    ->setParameter('filterStatut', $statutMap[$statutKey]);
            }
        }

        
        $query = $queryBuilder->getQuery()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);
        
        // Calculate pagination
        $paginator = new Paginator($query, true);
        $total = count($paginator);
        $totalPages = (int) ceil($total / $limit);
        $evenements = iterator_to_array($paginator->getIterator());
        $ticketsSold = [];
        foreach ($evenements as $evenement) {
            $ticketsSold[$evenement->getId()] = $ticketRepository->count(['evenement' => $evenement]);
        }
        
        return $this->render('event/events.html.twig', [
            'evenements' => $evenements,
            'tickets_sold' => $ticketsSold,
            'filter_value' => $filterValue,
            'status_filter' => $statusFilter,
            'sort' => $sort,
            'filter_params' => $filterParams,
            'current_page' => $page,
            'total_pages' => $totalPages,
        ]);
    }

    #[Route('/new', name: 'app_evenement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($evenement);
            $entityManager->flush();

            return $this->redirectToRoute('evenements', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evenement/new.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_evenement_show', methods: ['GET'])]
    public function show(Evenement $evenement): Response
    {
        $imageDataUri = null;
        $image = $evenement->getImageCouverture();
        
        if ($image !== null) {
            if (is_resource($image)) {
                $data = stream_get_contents($image);
            } elseif (is_string($image)) {
                $data = $image;
            } else {
                $data = null;
            }
            
            if ($data !== false && $data !== '' && $data !== null) {
                $imageDataUri = 'data:image/jpeg;base64,' . base64_encode($data);
            }
        }
        
        return $this->render('evenement/show.html.twig', [
            'evenement' => $evenement,
            'image' => $imageDataUri,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('evenements', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evenement/edit.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$evenement->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($evenement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('evenements', [], Response::HTTP_SEE_OTHER);
    }
}
