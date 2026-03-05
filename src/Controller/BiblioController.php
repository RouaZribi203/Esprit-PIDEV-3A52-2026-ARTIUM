<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Livre;
use App\Form\LivreType;
use App\Repository\LivreRepository;
use App\Enum\TypeOeuvre;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\LocationLivreRepository;

final class BiblioController extends AbstractController
{
    #[Route('/bibliotheque', name: 'livres')]
    public function index(Request $request, LivreRepository $livreRepository, LocationLivreRepository $locationRepo, PaginatorInterface $paginator): Response
    {
        $form = $this->createForm(LivreType::class);
        // separate empty form instance to be used for editing (populated client-side)
        $editForm = $this->createForm(LivreType::class);
        // search / sort / pagination parameters (GET)
        $q = $request->query->get('q');
        $sort = $request->query->get('sort');
        $queryBuilder = $livreRepository->createQueryBuilder('l');

        if ($q) {
            $queryBuilder
            ->andWhere('l.titre LIKE :q')
            ->setParameter('q', '%' . $q . '%');
        }

        if ($sort === 'asc') {
            $queryBuilder->orderBy('l.titre', 'ASC');
        } elseif ($sort === 'desc') {
            $queryBuilder->orderBy('l.titre', 'DESC');
        } else {
            $queryBuilder->orderBy('l.id', 'DESC');
        }

        $livres = $paginator->paginate(
            $queryBuilder, 
            $request->query->getInt('page', 1),
            8 // 👈 LIMIT PER PAGE (as you requested)
        );

        $statusMap = [];

foreach ($livres as $livre) {

    $isActive = false;
    $activeLocation = null;
    $expiration = null;

    foreach ($livre->getLocationLivres() as $loc) {

        if (!$loc->getEtat() || $loc->getEtat()->value !== 'Active') {
            continue;
        }

        $start = $loc->getDateDebut();
        if (!$start) {
            continue;
        }

        $days = $loc->getNombreDeJours();

        if (!$days || $days <= 0) {
            continue; // ignore invalid rentals
        }

        $expiration = (clone $start)->modify("+$days days");
        $now = new \DateTime();

        if ($start <= $now && $expiration > $now) {
            $isActive = true;
            $activeLocation = $loc;
            break;
        }
    }

    $statusMap[$livre->getId()] = [
        'isActive' => $isActive,
        'locationId' => $activeLocation?->getId() ?? null,
        'startDate' => $activeLocation?->getDateDebut()?->format('Y-m-d H:i:s') ?? null,
        'expirationDate' => isset($expiration) ? $expiration->format('Y-m-d H:i:s') : null
    ];
}

        return $this->render('biblio/livres.html.twig', [
            'controller_name' => 'BiblioController',
            'livreForm' => $form->createView(),
            'livreEditForm' => $editForm->createView(),
            'livres' => $livres,
            'livreStatus' => $statusMap,
            'search_q' => $q,
            'search_sort' => $sort,
        ]);
    }

    #[Route('/bibliotheque/new', name: 'livre_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $livre = new Livre();
        $form = $this->createForm(LivreType::class, $livre);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            // Ensure mapped scalar fields are explicitly set on the entity (covers inherited fields)
            $titre = $form->get('titre')->getData();
            if ($titre !== null) {
                $livre->setTitre($titre);
            }

            $description = $form->get('description')->getData();
            if ($description !== null) {
                $livre->setDescription($description);
            }

            $categorie = $form->get('categorie')->getData();
            if ($categorie !== null) {
                $livre->setCategorie($categorie);
            }

            $prix = $form->get('prix_location')->getData();
            if ($prix !== null && $prix !== '') {
                $livre->setPrixLocation((float) $prix);
            }

            // Explicitly set the Collection relation (ManyToOne)
            if ($form->has('collection')) {
                $collection = $form->get('collection')->getData();
                if ($collection !== null) {
                    $livre->setCollection($collection);
                }
            }

            // set required Oeuvre fields if not already set
            if (null === $livre->getDateCreation()) {
                $livre->setDateCreation(new \DateTime());
            }
            if (null === $livre->getType()) {
                $livre->setType(TypeOeuvre::LIVRE);
            }

            // handle uploaded image (store as blob) safely
            try {
                $imageFile = $form->get('image')->getData();
                if ($imageFile) {
                    $path = $imageFile->getPathname();
                    if (is_readable($path)) {
                       $livre->setImage(file_get_contents($path));
                    }
                }
            } catch (\Throwable $e) {
                // swallow file errors to avoid blocking persistence; validation should catch size/type
            }

            // handle uploaded pdf (store as blob) safely
            try {
                $pdfFile = $form->get('fichier_pdf')->getData();
                if ($pdfFile) {
                    $path = $pdfFile->getPathname();
                    if (is_readable($path)) {
                        $livre->setFichierPdf(file_get_contents($path));
                    }
                }
            } catch (\Throwable $e) {
                // swallow file errors
            }

            // Only persist when the form is valid
            if ($form->isValid()) {
                $em->persist($livre);
                $em->flush();

                $this->addFlash('success', 'Livre créé avec succès.');

                return $this->redirectToRoute('livres');
            }
        }

        // If invalid, render the index with form errors
        return $this->render('biblio/livres.html.twig', [
            'controller_name' => 'BiblioController',
            'livreForm' => $form->createView(),
        ]);
    }

    #[Route('/bibliotheque/livre/{id}/edit', name: 'livre_edit', methods: ['POST'])]
    public function edit(Livre $livre, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(LivreType::class, $livre);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // scalar fields
            $titre = $form->get('titre')->getData();
            if ($titre !== null) {
                $livre->setTitre($titre);
            }

            $description = $form->get('description')->getData();
            if ($description !== null) {
                $livre->setDescription($description);
            }

            $categorie = $form->get('categorie')->getData();
            if ($categorie !== null) {
                $livre->setCategorie($categorie);
            }

            $prix = $form->get('prix_location')->getData();
            if ($prix !== null && $prix !== '') {
                $livre->setPrixLocation((float) $prix);
            }

            if ($form->has('collection')) {
                $collection = $form->get('collection')->getData();
                if ($collection !== null) {
                    $livre->setCollection($collection);
                }
            }

            // handle replacing image
            try {
                $imageFile = $form->get('image')->getData();
                if ($imageFile) {
                    $path = $imageFile->getPathname();
                    if (is_readable($path)) {
                        $livre->setImage(file_get_contents($path));
                    }
                }
            } catch (\Throwable $e) {
                // ignore image errors
            }

            // handle replacing pdf
            try {
                $pdfFile = $form->get('fichier_pdf')->getData();
                if ($pdfFile) {
                    $path = $pdfFile->getPathname();
                    if (is_readable($path)) {
                        $livre->setFichierPdf(file_get_contents($path));
                    }
                }
            } catch (\Throwable $e) {
                // ignore pdf errors
            }

            if ($form->isValid()) {
                $em->persist($livre);
                $em->flush();

                $this->addFlash('success', 'Livre mis à jour avec succès.');

                return $this->redirectToRoute('livres');
            }
        }

        $this->addFlash('error', 'Impossible de mettre à jour le livre.');
        return $this->redirectToRoute('livres');
    }

    #[Route('/bibliotheque/livre/{id}/delete', name: 'livre_delete', methods: ['POST'])]
    public function delete(Livre $livre, EntityManagerInterface $em): Response
    {
        $em->remove($livre);
        $em->flush();

        $this->addFlash('success', 'Livre supprimé avec succès.');

        return $this->redirectToRoute('livres');
    }

    #[Route('/bibliotheque/livre/{id}/pdf', name: 'livre_pdf', methods: ['GET'])]
    public function pdf(Livre $livre, Request $request): Response
    {
        $data = $livre->getFichierPdf();
        if (!$data) {
            throw $this->createNotFoundException('PDF introuvable');
        }

        // normalize blob/resource to bytes string so we can compute length and handle ranges
        if (is_resource($data)) {
            try { @rewind($data); } catch (\Throwable $e) {}
            $bytes = stream_get_contents($data);
        } else {
            $bytes = $data;
        }

        $length = mb_strlen((string) $bytes, '8bit');
        $download = $request->query->get('download');
        $disposition = $download ? 'attachment; filename="livre-' . $livre->getId() . '.pdf"' : 'inline; filename="livre-' . $livre->getId() . '.pdf"';

        $range = $request->headers->get('range');
        if ($range && $length > 0) {
            // Example Range: bytes=0-499
            if (preg_match('/bytes=(\d*)-(\d*)/', $range, $matches)) {
                $start = ($matches[1] !== '') ? (int)$matches[1] : 0;
                $end = ($matches[2] !== '') ? (int)$matches[2] : ($length - 1);
                if ($end >= $length) {
                    $end = $length - 1;
                }
                if ($start > $end) {
                    // invalid range
                    $response = new Response('', 416);
                    $response->headers->set('Content-Range', 'bytes */' . $length);
                    return $response;
                }

                $part = substr($bytes, $start, $end - $start + 1);
                $response = new Response($part, 206);
                $response->headers->set('Content-Type', 'application/pdf');
                $response->headers->set('Content-Disposition', $disposition);
                $response->headers->set('Accept-Ranges', 'bytes');
                $response->headers->set('Content-Range', sprintf('bytes %d-%d/%d', $start, $end, $length));
                $response->headers->set('Content-Length', (string)strlen($part));
                return $response;
            }
        }

        // full response
        $response = new Response($bytes);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Length', (string)$length);
        $response->headers->set('Accept-Ranges', 'bytes');
        return $response;
    }

    #[Route('/bibliotheque/livre/{id}/image', name: 'livre_image', methods: ['GET'])]
    public function image(Livre $livre): Response
    {
        $data = $livre->getImage();
        if (!$data) {
            throw $this->createNotFoundException('Image introuvable');
        }

        // normalize resource to string
        if (is_resource($data)) {
            try {
                rewind($data);
            } catch (\Throwable $e) {}
            $bytes = stream_get_contents($data);
        } else {
            $bytes = $data;
        }

        $mime = 'application/octet-stream';
        if (function_exists('finfo_buffer')) {
            $f = new \finfo(FILEINFO_MIME_TYPE);
            $detected = $f->buffer($bytes);
            if ($detected) {
                $mime = $detected;
            }
        }

        $response = new Response($bytes);
        $response->headers->set('Content-Type', $mime);
        $response->headers->set('Content-Disposition', 'inline; filename="livre-' . $livre->getId() . '"');

        return $response;
    }
}
