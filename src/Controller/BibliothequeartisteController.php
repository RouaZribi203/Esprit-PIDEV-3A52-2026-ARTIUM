<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Livre;
use App\Entity\User;
use App\Form\LivreType;
use App\Repository\LivreRepository;
use App\Repository\CollectionsRepository;
use App\Enum\TypeOeuvre;

final class BibliothequeartisteController extends AbstractController
{
    #[Route('/artiste-bibliotheque', name: 'app_bibliothequeartiste')]
    public function index(
        LivreRepository $livreRepository,
        CollectionsRepository $collectionsRepository
    ): Response {
        $artist = $this->getUser();

        if (!$artist instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // 🔥 Create Symfony form for Twig
        $form = $this->createForm(LivreType::class, new Livre(), [
            'artist' => $artist,
        ]);

        // Fetch collections
        $collections = $collectionsRepository->createQueryBuilder('c')
            ->where('IDENTITY(c.artiste) = :artistId')
            ->setParameter('artistId', $artist->getId())
            ->getQuery()
            ->getResult();

        // Fetch books
        $livres = $livreRepository->createQueryBuilder('l')
            ->join('l.collection', 'c')
            ->where('IDENTITY(c.artiste) = :artistId')
            ->setParameter('artistId', $artist->getId())
            ->orderBy('l.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render(
            'Front Office/bibliothequeartiste/bibliothequeartiste.html.twig',
            [
                'livres' => $livres,
                'collections' => $collections,
                'livreForm' => $form->createView(), // 🔥 REQUIRED
            ]
        );
    }

    #[Route('/artiste-bibliotheque/new', name: 'artiste_livre_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        CollectionsRepository $collectionsRepository,
        LivreRepository $livreRepository
    ): Response {
        $artist = $this->getUser();

        if (!$artist instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $livre = new Livre();

        $form = $this->createForm(LivreType::class, $livre, [
            'artist' => $artist,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $collection = $form->get('collection')->getData();

            if (!$collection || $collection->getArtiste()->getId() !== $artist->getId()) {
                throw $this->createAccessDeniedException();
            }

            $livre->setCollection($collection);
            $livre->setDateCreation(new \DateTime());
            $livre->setType(TypeOeuvre::LIVRE);

            // Image
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $livre->setImage(file_get_contents($imageFile->getPathname()));
            }

            // PDF
            $pdfFile = $form->get('fichier_pdf')->getData();
            if ($pdfFile) {
                $livre->setFichierPdf(file_get_contents($pdfFile->getPathname()));
            }

            $em->persist($livre);
            $em->flush();

            return $this->redirectToRoute('app_bibliothequeartiste');
        }

        // If invalid, just redirect back
        return $this->redirectToRoute('app_bibliothequeartiste');
    }

    #[Route('/artiste-bibliotheque/livre/{id}/edit', name: 'artiste_livre_edit', methods: ['POST'])]
public function edit(
    Livre $livre,
    Request $request,
    EntityManagerInterface $em
): Response {

    $artist = $this->getUser();

    if (
        !$artist instanceof User ||
        $livre->getCollection()->getArtiste()->getId() !== $artist->getId()
    ) {
        throw $this->createAccessDeniedException();
    }

    // MANUAL UPDATE (since you use manual form)

    $data = $request->request->all('livre');

    if ($data) {

        if (isset($data['titre'])) {
            $livre->setTitre($data['titre']);
        }

        if (isset($data['description'])) {
            $livre->setDescription($data['description']);
        }

        if (isset($data['categorie'])) {
            $livre->setCategorie($data['categorie']);
        }

        if (isset($data['prix_location'])) {
            $livre->setPrixLocation((float)$data['prix_location']);
        }

        if (isset($data['collection'])) {
            $collectionId = $data['collection'];
            $collection = $em->getRepository(\App\Entity\Collections::class)
                ->find($collectionId);

            if ($collection && $collection->getArtiste()->getId() === $artist->getId()) {
                $livre->setCollection($collection);
            }
        }

        // Handle image
        $imageFile = $request->files->get('livre')['image'] ?? null;
        if ($imageFile) {
            $livre->setImage(file_get_contents($imageFile->getPathname()));
        }

        // Handle pdf
        $pdfFile = $request->files->get('livre')['fichier_pdf'] ?? null;
        if ($pdfFile) {
            $livre->setFichierPdf(file_get_contents($pdfFile->getPathname()));
        }

        $em->flush();
    }

    return $this->redirectToRoute('app_bibliothequeartiste');
}

    #[Route('/artiste-bibliotheque/livre/{id}/delete', name: 'artiste_livre_delete', methods: ['POST'])]
    public function delete(
        Livre $livre,
        EntityManagerInterface $em
    ): Response {
        $artist = $this->getUser();

        if (
            !$artist instanceof User ||
            $livre->getCollection()->getArtiste()->getId() !== $artist->getId()
        ) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($livre);
        $em->flush();

        return $this->redirectToRoute('app_bibliothequeartiste');
    }
}
