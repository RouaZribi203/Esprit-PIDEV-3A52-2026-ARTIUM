<?php

namespace App\Controller;

use App\Entity\Collections;
use App\Form\CollectionsType;
use App\Repository\CollectionsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/collections')]
final class CollectionsController extends AbstractController
{
    #[Route(name: 'app_collections_index', methods: ['GET'])]
    public function index(CollectionsRepository $collectionsRepository): Response
    {
        return $this->render('collections/index.html.twig', [
            'collections' => $collectionsRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_collections_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $collection = new Collections();
        $form = $this->createForm(CollectionsType::class, $collection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($collection);
            $entityManager->flush();

            return $this->redirectToRoute('app_collections_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('collections/new.html.twig', [
            'collection' => $collection,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_collections_show', methods: ['GET'])]
    public function show(Collections $collection): Response
    {
        return $this->render('collections/show.html.twig', [
            'collection' => $collection,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_collections_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Collections $collection, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CollectionsType::class, $collection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_collections_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('collections/edit.html.twig', [
            'collection' => $collection,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_collections_delete', methods: ['POST'])]
    public function delete(Request $request, Collections $collection, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$collection->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($collection);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_collections_index', [], Response::HTTP_SEE_OTHER);
    }
}
