<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\Reponse;
use App\Entity\User;
use App\Enum\StatutReclamation;
use App\Form\ReponseType;
use App\Repository\ReclamationRepository;
use App\Repository\ReponseRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reponse')]
final class ReponseController extends AbstractController
{
    #[Route(name: 'app_reponse_index', methods: ['GET'])]
    public function index(ReponseRepository $reponseRepository): Response
    {
        return $this->render('reponse/index.html.twig', [
            'reponses' => $reponseRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_reponse_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reponse = new Reponse();
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($reponse->getDateReponse() === null) {
                $reponse->setDateReponse(new \DateTime());
            }
            if ($reponse->getUserAdmin() === null && $this->getUser() instanceof \App\Entity\User) {
                $reponse->setUserAdmin($this->getUser());
            }
            $entityManager->persist($reponse);
            $entityManager->flush();

            return $this->redirectToRoute('app_reponse_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reponse/new.html.twig', [
            'reponse' => $reponse,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_reponse_show', methods: ['GET'])]
    public function show(Reponse $reponse): Response
    {
        return $this->render('reponse/show.html.twig', [
            'reponse' => $reponse,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reponse_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reponse_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reponse/edit.html.twig', [
            'reponse' => $reponse,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_reponse_delete', methods: ['POST'])]
    public function delete(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reponse->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reponse);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reponse_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/admin/reclamation/{id}', name: 'app_reponse_admin_create', methods: ['POST'])]
    public function adminCreate(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager, UserRepository $userRepository, ReclamationRepository $reclamationRepository): Response
    {
        $reponse = new Reponse();
        $reponse->setReclamation($reclamation);

        $form = $this->createForm(ReponseType::class, $reponse);
        $form->remove('reclamation');
        $form->remove('user_admin');
        $form->remove('date_reponse');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if (!$user instanceof User) {
                $user = $userRepository->find(1);
            }
            
            if (!$user instanceof User) {
                throw $this->createNotFoundException('Utilisateur introuvable.');
            }
            
            $reponse->setUserAdmin($user);
            
            if ($reponse->getDateReponse() === null) {
                $reponse->setDateReponse(new \DateTime());
            }

            // Changer automatiquement le statut à "Traitée" car l'admin a répondu
            $reclamation->setStatut(StatutReclamation::TRAITEE);

            $entityManager->persist($reponse);
            $entityManager->flush();

            return $this->redirectToRoute('reclamations', [], Response::HTTP_SEE_OTHER);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $reclamations = $reclamationRepository->findBy([], ['date_creation' => 'DESC']);

            $responseCreateForms = [];
            $responseEditForms = [];

            foreach ($reclamations as $existingReclamation) {
                if ($existingReclamation->getId() === $reclamation->getId()) {
                    $responseCreateForms[$existingReclamation->getId()] = $form->createView();
                } else {
                    $newReponse = new Reponse();
                    $newReponse->setReclamation($existingReclamation);
                    $createForm = $this->createForm(ReponseType::class, $newReponse);
                    $createForm->remove('reclamation');
                    $createForm->remove('user_admin');
                    $createForm->remove('date_reponse');
                    $responseCreateForms[$existingReclamation->getId()] = $createForm->createView();
                }

                foreach ($existingReclamation->getReponses() as $existingReponse) {
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
                'search_query' => '',
            ]);
        }

        return $this->redirectToRoute('reclamations', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/admin/{id}/edit', name: 'app_reponse_admin_edit', methods: ['POST'])]
    public function adminEdit(Request $request, Reponse $reponse, EntityManagerInterface $entityManager, ReclamationRepository $reclamationRepository): Response
    {
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->remove('reclamation');
        $form->remove('user_admin');
        $form->remove('date_reponse');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('reclamations', [], Response::HTTP_SEE_OTHER);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $reclamations = $reclamationRepository->findBy([], ['date_creation' => 'DESC']);

            $responseCreateForms = [];
            $responseEditForms = [];

            foreach ($reclamations as $existingReclamation) {
                $newReponse = new Reponse();
                $newReponse->setReclamation($existingReclamation);
                $createForm = $this->createForm(ReponseType::class, $newReponse);
                $createForm->remove('reclamation');
                $createForm->remove('user_admin');
                $createForm->remove('date_reponse');
                $responseCreateForms[$existingReclamation->getId()] = $createForm->createView();

                foreach ($existingReclamation->getReponses() as $existingReponse) {
                    if ($existingReponse->getId() === $reponse->getId()) {
                        $responseEditForms[$existingReponse->getId()] = $form->createView();
                        continue;
                    }

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
                'search_query' => '',
            ]);
        }

        return $this->redirectToRoute('reclamations', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/admin/{id}', name: 'app_reponse_admin_delete', methods: ['POST'])]
    public function adminDelete(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_response'.$reponse->getId(), $request->getPayload()->getString('_token'))) {
            $reclamation = $reponse->getReclamation();
            
            $entityManager->remove($reponse);
            $entityManager->flush();
            
            // Si aucune réponse ne reste, remettre le statut à "Non traitée"
            if ($reclamation && $reclamation->getReponses()->count() === 0) {
                $reclamation->setStatut(StatutReclamation::NON_TRAITEE);
                $entityManager->flush();
            }
        }

        return $this->redirectToRoute('reclamations', [], Response::HTTP_SEE_OTHER);
    }
}
