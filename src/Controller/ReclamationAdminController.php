<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\Reponse;
use App\Form\ReclamationType;
use App\Form\ReponseType;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reclamation/admin')]
final class ReclamationAdminController extends AbstractController
{
    #[Route(name: 'reclamations', methods: ['GET'])]
    public function index(Request $request, ReclamationRepository $reclamationRepository): Response
    {
        $responseCreateForms = [];
        $responseEditForms = [];
        
        // Récupérer les paramètres de recherche
        $search = trim((string) $request->query->get('q', ''));
        
        // Filtrer les réclamations selon la recherche
        if ($search !== '') {
            $reclamations = $reclamationRepository->createQueryBuilder('r')
                ->where('r.texte LIKE :search')
                ->orWhere('r.id LIKE :search')
                ->setParameter('search', '%' . $search . '%')
                ->orderBy('r.date_creation', 'DESC')
                ->getQuery()
                ->getResult();
        } else {
            $reclamations = $reclamationRepository->findBy([], ['date_creation' => 'DESC']);
        }

        foreach ($reclamations as $reclamation) {
            $reponse = new Reponse();
            $reponse->setReclamation($reclamation);
            $createForm = $this->createForm(ReponseType::class, $reponse);
            $createForm->remove('reclamation');
            $createForm->remove('user_admin');
            $createForm->remove('date_reponse');
            $responseCreateForms[$reclamation->getId()] = $createForm->createView();

            foreach ($reclamation->getReponses() as $existingReponse) {
                $editForm = $this->createForm(ReponseType::class, $existingReponse);
                $editForm->remove('reclamation');
                $editForm->remove('user_admin');
                $editForm->remove('date_reponse');
                $responseEditForms[$existingReponse->getId()] = $editForm->createView();
            }
        }

        return $this->render('reclam/reclams.html.twig', [
            'reclamations' => $reclamations,
            'responseCreateForms' => $responseCreateForms,
            'responseEditForms' => $responseEditForms,
            'search_query' => $search,
        ]);
    }

    #[Route('/new', name: 'app_reclamation_admin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reclamation);
            $entityManager->flush();

            return $this->redirectToRoute('reclamations', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reclamation_admin/new.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_reclamation_admin_show', methods: ['GET'])]
    public function show(Reclamation $reclamation): Response
    {
        return $this->render('reclamation_admin/show.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reclamation_admin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('reclamations', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reclamation_admin/edit.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_reclamation_admin_delete', methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reclamation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reclamation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reclamation_admin_index', [], Response::HTTP_SEE_OTHER);
        return $this->redirectToRoute('reclamations', [], Response::HTTP_SEE_OTHER);
    }
}
