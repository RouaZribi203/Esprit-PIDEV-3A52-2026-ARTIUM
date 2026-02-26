<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $livre = new Livre();

        $form = $this->createForm(LivreType::class, $livre, [
            'artist' => null, // admin sees all collections
        ]);

        $editForm = $this->createForm(LivreType::class, null, [
            'artist' => null,
        ]);

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
        'isActive' => $isActive ?? false,
        'locationId' => $activeLocation?->getId() ?? null,
        'startDate' => $activeLocation?->getDateDebut()?->format('Y-m-d H:i:s') ?? null,
        'expirationDate' => isset($expiration) ? $expiration->format('Y-m-d H:i:s') : null
    ];
}

        return $this->render('biblio/livres.html.twig', [
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

        $form = $this->createForm(LivreType::class, $livre, [
            'artist' => null,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Required Oeuvre fields
            if (null === $livre->getDateCreation()) {
                $livre->setDateCreation(new \DateTime());
            }

            if (null === $livre->getType()) {
                $livre->setType(TypeOeuvre::LIVRE);
            }

            // Image upload
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $path = $imageFile->getPathname();
                if (is_readable($path)) {
                    $livre->setImage(file_get_contents($path));
                }
            }

            // PDF upload
            $pdfFile = $form->get('fichier_pdf')->getData();
            if ($pdfFile) {
                $path = $pdfFile->getPathname();
                if (is_readable($path)) {
                    $livre->setFichierPdf(file_get_contents($path));
                }
            }

            $em->persist($livre);
            $em->flush();

            $this->addFlash('success', 'Livre créé avec succès.');

            return $this->redirectToRoute('livres');
        }

        return $this->redirectToRoute('livres');
    }

    #[Route('/bibliotheque/livre/{id}/edit', name: 'livre_edit', methods: ['POST'])]
    public function edit(Livre $livre, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(LivreType::class, $livre, [
            'artist' => null,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Replace image if new uploaded
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $path = $imageFile->getPathname();
                if (is_readable($path)) {
                    $livre->setImage(file_get_contents($path));
                }
            }

            // Replace PDF if new uploaded
            $pdfFile = $form->get('fichier_pdf')->getData();
            if ($pdfFile) {
                $path = $pdfFile->getPathname();
                if (is_readable($path)) {
                    $livre->setFichierPdf(file_get_contents($path));
                }
            }

            $em->flush();

            $this->addFlash('success', 'Livre mis à jour avec succès.');

            return $this->redirectToRoute('livres');
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

        if (is_resource($data)) {
            rewind($data);
            $bytes = stream_get_contents($data);
        } else {
            $bytes = $data;
        }

        $length = mb_strlen($bytes, '8bit');
        $download = $request->query->get('download');
        $disposition = $download
            ? 'attachment; filename="livre-' . $livre->getId() . '.pdf"'
            : 'inline; filename="livre-' . $livre->getId() . '.pdf"';

        $response = new Response($bytes);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Length', (string)$length);

        return $response;
    }

    #[Route('/bibliotheque/livre/{id}/image', name: 'livre_image', methods: ['GET'])]
    public function image(Livre $livre): Response
    {
        $data = $livre->getImage();

        if (!$data) {
            throw $this->createNotFoundException('Image introuvable');
        }

        if (is_resource($data)) {
            rewind($data);
            $bytes = stream_get_contents($data);
        } else {
            $bytes = $data;
        }

        $response = new Response($bytes);
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'inline; filename="livre-' . $livre->getId() . '"');

        return $response;
    }
}
