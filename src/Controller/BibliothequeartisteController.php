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
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\TypeOeuvre;


final class BibliothequeartisteController extends AbstractController
{
    #[Route('/artiste-bibliotheque', name: 'app_bibliothequeartiste')]
    public function index(CollectionsRepository $collectionsRepository, LivreRepository $livreRepository, UserRepository $userRepository, LocationLivreRepository $locationLivreRepository): Response
    {
        
        $artist = $this->getUser();



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

    #[Route('/artiste-bibliotheque/new', name: 'artiste_livre_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, CollectionsRepository $collectionsRepository, UserRepository $userRepository): Response
    {
        // TODO: Replace test artist with $this->getUser() when authentication module is merged
        $artist = $this->getUser();
        if (!$artist) {
            $this->addFlash('error', 'Artiste de test introuvable.');
            return $this->redirectToRoute('app_bibliothequeartiste');
        }

        $livre = new Livre();

        // scalar fields
        $titre = $request->request->get('titre');
        if ($titre !== null) {
            $livre->setTitre($titre);
        }

        $description = $request->request->get('description');
        if ($description !== null) {
            $livre->setDescription($description);
        }

        $categorie = $request->request->get('categorie');
        if ($categorie !== null) {
            $livre->setCategorie($categorie);
        }

        $prix = $request->request->get('prix_location');
        if ($prix !== null && $prix !== '') {
            $livre->setPrixLocation((float) $prix);
        }

        // collection: ensure it belongs to the test artist (ID 1)
        $collectionId = $request->request->get('collection');
        $collection = null;
        if ($collectionId) {
            $collection = $collectionsRepository->find((int)$collectionId);
            if (!$collection ||!$collection->getArtiste() ||$collection->getArtiste()->getId() !== $artist->getId()) 
                {
                $this->addFlash('error', 'Collection invalide.');
                return $this->redirectToRoute('app_bibliothequeartiste');
            }
        } else {
            // if no collection provided, assign the first collection for this test artist
            $cols = $collectionsRepository->findBy(['artiste' => $artist]);
            if (count($cols) > 0) {
                $collection = $cols[0];
            }
        }

        if ($collection && method_exists($livre, 'setCollection')) {
            $livre->setCollection($collection);
        }

        // set required Oeuvre fields
        if (null === $livre->getDateCreation()) {
            $livre->setDateCreation(new \DateTime());
        }
        if (null === $livre->getType()) {
            $livre->setType(TypeOeuvre::LIVRE);
        }

        // handle uploaded image
        try {
            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $path = $imageFile->getPathname();
                if (is_readable($path)) {
                    $livre->setImage(file_get_contents($path));
                }
            }
        } catch (\Throwable $e) {
            // ignore image errors
        }

        // handle uploaded pdf
        try {
            $pdfFile = $request->files->get('fichier_pdf');
            if ($pdfFile) {
                $path = $pdfFile->getPathname();
                if (is_readable($path)) {
                    $livre->setFichierPdf(file_get_contents($path));
                }
            }
        } catch (\Throwable $e) {
            // ignore pdf errors
        }

        $em->persist($livre);
        $em->flush();

        $this->addFlash('success', 'Livre créé avec succès.');

        return $this->redirectToRoute('app_bibliothequeartiste');
    }

    #[Route('/artiste-bibliotheque/livre/{id}/edit', name: 'artiste_livre_edit', methods: ['POST'])]
    public function edit(Livre $livre, Request $request, EntityManagerInterface $em, CollectionsRepository $collectionsRepository, UserRepository $userRepository): Response
    {
        // TODO: Replace test artist with $this->getUser() when authentication module is merged
        $artist = $this->getUser();
        if (!$artist) {
            $this->addFlash('error', 'Artiste de test introuvable.');
            return $this->redirectToRoute('app_bibliothequeartiste');
        }

        // read submitted 'livre' array safely by using ->all() to avoid InputBag scalar checks
        $all = $request->request->all();
        $data = (isset($all['livre']) && \is_array($all['livre'])) ? $all['livre'] : [];

        // ensure the livre's collection belongs to the artist (prevent reassigning to other artists)
        $collectionId = $data['collection'] ?? null;
        if ($collectionId) {
            $collection = $collectionsRepository->find((int)$collectionId);
            if (!$collection ||!$collection->getArtiste() ||$collection->getArtiste()->getId() !== $artist->getId()) 
            {
                $this->addFlash('error', 'Collection invalide.');
                return $this->redirectToRoute('app_bibliothequeartiste');
            }
            if (method_exists($livre, 'setCollection')) {
                $livre->setCollection($collection);
            }
        }

        // scalar fields (from the 'livre' array)
        $titre = $data['titre'] ?? null;
        if ($titre !== null) {
            $livre->setTitre($titre);
        }

        $description = $data['description'] ?? null;
        if ($description !== null) {
            $livre->setDescription($description);
        }

        $categorie = $data['categorie'] ?? null;
        if ($categorie !== null) {
            $livre->setCategorie($categorie);
        }

        $prix = $data['prix_location'] ?? null;
        if ($prix !== null && $prix !== '') {
            $livre->setPrixLocation((float) $prix);
        }

        // handle replacing image/pdf via the 'livre' files array
        try {
            $allFiles = $request->files->all();
            $files = (isset($allFiles['livre']) && \is_array($allFiles['livre'])) ? $allFiles['livre'] : [];
            $imageFile = array_key_exists('image', $files) ? $files['image'] : null;
            if ($imageFile) {
                $path = $imageFile->getPathname();
                if (is_readable($path)) {
                    $livre->setImage(file_get_contents($path));
                }
            }
        } catch (\Throwable $e) {
            // ignore image errors
        }

        try {
            $allFiles = $request->files->all();
            $files = (isset($allFiles['livre']) && \is_array($allFiles['livre'])) ? $allFiles['livre'] : [];
            $pdfFile = array_key_exists('fichier_pdf', $files) ? $files['fichier_pdf'] : null;
            if ($pdfFile) {
                $path = $pdfFile->getPathname();
                if (is_readable($path)) {
                    $livre->setFichierPdf(file_get_contents($path));
                }
            }
        } catch (\Throwable $e) {
            // ignore pdf errors
        }

        $em->persist($livre);
        $em->flush();

        $this->addFlash('success', 'Livre mis à jour avec succès.');

        return $this->redirectToRoute('app_bibliothequeartiste');
    }

#[Route('/artiste-bibliotheque/livre/{id}/delete', name: 'artiste_livre_delete', methods: ['POST'])]
public function delete(
    Livre $livre,
    EntityManagerInterface $em,
    LocationLivreRepository $locationLivreRepository
): Response
{
    if (!$livre) {
        $this->addFlash('error', 'Livre introuvable.');
        return $this->redirectToRoute('app_bibliothequeartiste');
    }

    // Check if there is an ACTIVE rental
    $activeLocation = $locationLivreRepository->findOneBy([
        'livre' => $livre,
        'etat' => EtatLocation::ACTIVE
    ]);

    if ($activeLocation) {
        $this->addFlash(
            'error',
            "Ce livre est actuellement loué. Vous ne pouvez pas le supprimer avant l'expiration."
        );

        return $this->redirectToRoute('app_bibliothequeartiste');
    }

    $em->remove($livre);
    $em->flush();

    $this->addFlash('success', 'Livre supprimé avec succès.');

    return $this->redirectToRoute('app_bibliothequeartiste');
}

}

   

