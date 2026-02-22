<?php

namespace App\Controller;

use App\Enum\TypeEvenement;
use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EventsfrontController extends AbstractController
{
    #[Route('/user-evenements', name: 'app_eventsfront')]
    public function index(EvenementRepository $evenementRepository): Response
    {
        $evenements = $evenementRepository->findBy([], ['date_debut' => 'DESC']);
        $types = TypeEvenement::cases();

        $grouped = [];
        foreach ($types as $type) {
            $grouped[$type->value] = [];
        }

        $allRows = [];
        foreach ($evenements as $evenement) {
            $row = [
                'evenement' => $evenement,
                'image' => $this->getImageDataUri($evenement->getImageCouverture()),
            ];
            $allRows[] = $row;

            $typeValue = $evenement->getType()?->value;
            if ($typeValue && array_key_exists($typeValue, $grouped)) {
                $grouped[$typeValue][] = $row;
            }
        }

        return $this->render('Front Office/eventsfront/eventsfront.html.twig', [
            'types' => $types,
            'all_rows' => $allRows,
            'grouped_rows' => $grouped,
        ]);
    }

    private function getImageDataUri(mixed $image): ?string
    {
        if ($image === null) {
            return null;
        }

        if (is_resource($image)) {
            $data = stream_get_contents($image);
        } elseif (is_string($image)) {
            $data = $image;
        } else {
            return null;
        }

        if ($data === false || $data === '') {
            return null;
        }

        return 'data:image/jpeg;base64,' . base64_encode($data);
    }

    #[Route('/search-events', name: 'app_eventsfrontkeyword', methods: ['GET'])]
    public function searchByKeyword(Request $request, EvenementRepository $evenementRepository): Response
    {
        $query = $request->query->get('query', '');
        
        if (empty($query)) {
            return $this->redirectToRoute('app_eventsfront');
        }

        $evenements = $evenementRepository->findAll();
        $types = TypeEvenement::cases();

        $grouped = [];
        foreach ($types as $type) {
            $grouped[$type->value] = [];
        }

        $searchResults = [];
        foreach ($evenements as $evenement) {
            if (stripos($evenement->getTitre() ?? '', $query) !== false) {
                $row = [
                    'evenement' => $evenement,
                    'image' => $this->getImageDataUri($evenement->getImageCouverture()),
                ];
                $searchResults[] = $row;

                $typeValue = $evenement->getType()?->value;
                if ($typeValue && array_key_exists($typeValue, $grouped)) {
                    $grouped[$typeValue][] = $row;
                }
            }
        }

        return $this->render('Front Office/eventsfront/eventsfront.html.twig', [
            'types' => $types,
            'all_rows' => $searchResults,
            'grouped_rows' => $grouped,
        ]);
    }
}
