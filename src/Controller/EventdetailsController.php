<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EventdetailsController extends AbstractController
{
    #[Route('/details-evenement/{id}', name: 'app_eventdetails', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        Evenement $evenement,
        TicketRepository $ticketRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            $fallback = $userRepository->find(1);
            if ($fallback instanceof User) {
                $user = $fallback;
            } else {
                throw $this->createAccessDeniedException();
            }
        }

        if ($request->isMethod('POST') && $this->isCsrfTokenValid('buy_ticket_' . $evenement->getId(), $request->request->get('_token'))) {
            $payload = $this->buildTicketPayload($evenement, $user);

            $ticket = new Ticket();
            $ticket->setEvenement($evenement);
            $ticket->setUser($user);
            $ticket->setDateAchat(new \DateTime());
            $ticket->setCodeQr($payload);

            $entityManager->persist($ticket);
            $entityManager->flush();

            return $this->redirectToRoute('app_eventdetails', ['id' => $evenement->getId()]);
        }

        $tickets = $ticketRepository->findBy(
            ['evenement' => $evenement, 'user' => $user],
            ['date_achat' => 'DESC']
        );

        return $this->render('Front Office/eventdetails/eventdetails.html.twig', [
            'evenement' => $evenement,
            'image' => $this->getImageDataUri($evenement->getImageCouverture()),
            'tickets' => $tickets,
        ]);
    }

    #[Route('/ticket/{id}/qr', name: 'app_ticket_qr', methods: ['GET'])]
    public function ticketQr(Ticket $ticket, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            $fallback = $userRepository->find(1);
            if ($fallback instanceof User) {
                $user = $fallback;
            } else {
                throw $this->createAccessDeniedException();
            }
        }

        if ($ticket->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $payload = $this->extractTicketPayload($ticket);
        $qrCode = QrCode::create($payload)->setSize(240)->setMargin(10);
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);

        return new Response($result->getString(), Response::HTTP_OK, [
            'Content-Type' => 'image/svg+xml',
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

    private function buildTicketPayload(Evenement $evenement, User $user): string
    {
        return json_encode([
            'evenement_id' => $evenement->getId(),
            'evenement' => $evenement->getTitre(),
            'user_id' => $user->getId(),
            'user' => trim($user->getNom() . ' ' . $user->getPrenom()),
            'issued_at' => (new \DateTime())->format('c'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

    private function extractTicketPayload(Ticket $ticket): string
    {
        $code = $ticket->getCodeQr();
        if (is_resource($code)) {
            $data = stream_get_contents($code);
        } elseif (is_string($code)) {
            $data = $code;
        } else {
            $data = null;
        }

        if ($data === null || $data === false || $data === '') {
            $data = (string) $ticket->getId();
        }

        return $data;
    }
}
