<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\User;
use App\Enum\StatutReclamation;
use App\Form\Reclamation1Type;
use App\Repository\ReclamationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReclamationArtisteController extends AbstractController
{
    #[Route('/artiste-reclamation', name: 'app_reclamationsartiste', methods: ['GET'])]
    public function index(Request $request, ReclamationRepository $reclamationRepository, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            $user = $userRepository->find(1);
        }

        $search = trim((string) $request->query->get('q', ''));
        $statutValue = $request->query->get('statut');
        $statut = null;
        if (is_string($statutValue) && $statutValue !== '') {
            $statut = StatutReclamation::tryFrom($statutValue);
        }

        $reclamations = [];
        if ($user instanceof User) {
            $reclamations = $reclamationRepository->findByUserFilters(
                $user,
                $search !== '' ? $search : null,
                $statut
            );
        }

        $form = $this->createForm(Reclamation1Type::class, new Reclamation(), [
            'action' => $this->generateUrl('app_reclamationartiste_new'),
            'method' => 'POST',
        ]);

        $editForms = [];
        foreach ($reclamations as $reclamation) {
            $editForms[$reclamation->getId()] = $this->createForm(Reclamation1Type::class, $reclamation, [
                'action' => $this->generateUrl('app_reclamationartiste_edit', ['id' => $reclamation->getId()]),
                'method' => 'POST',
            ])->createView();
        }

        return $this->render('Front Office/reclamationsartiste/reclamationsartiste.html.twig', [
            'reclamations' => $reclamations,
            'form' => $form->createView(),
            'edit_forms' => $editForms,
            'search_query' => $search ?? '',
            'selected_statut' => $statut?->value ?? '',
        ]);
    }

    #[Route('/artiste-reclamation/new', name: 'app_reclamationartiste_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $reclamation = new Reclamation();
        $form = $this->createForm(Reclamation1Type::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $user = $userRepository->find(1);
                if (!$user instanceof User) {
                    throw $this->createNotFoundException('Utilisateur par defaut introuvable.');
                }
            }

            $reclamation->setUser($user);

            if (null === $reclamation->getDateCreation()) {
                $reclamation->setDateCreation(new \DateTime());
            }

            if (null === $reclamation->getStatut()) {
                $reclamation->setStatut(StatutReclamation::NON_TRAITEE);
            }

            $entityManager->persist($reclamation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reclamationsartiste', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/artiste-reclamation/{id}/edit', name: 'app_reclamationartiste_edit', methods: ['POST'])]
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($user instanceof User && $reclamation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette reclamation.');
        }

        $form = $this->createForm(Reclamation1Type::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reclamationsartiste', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/artiste-reclamation/{id}/delete', name: 'app_reclamationartiste_delete', methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($user instanceof User && $reclamation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette reclamation.');
        }

        if ($this->isCsrfTokenValid('delete'.$reclamation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reclamation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reclamationsartiste', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/artiste-reclamation/{id}', name: 'app_reclamationartiste_show', methods: ['GET'])]
    public function show(Reclamation $reclamation): Response
    {
        $user = $this->getUser();
        if ($user instanceof User && $reclamation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas consulter cette reclamation.');
        }

        return $this->render('reclamation/show.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }
}
