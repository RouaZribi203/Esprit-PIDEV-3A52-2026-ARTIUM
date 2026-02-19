<?php

namespace App\Twig;

use App\Entity\User;
use App\Repository\TicketRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NotificationExtension extends AbstractExtension
{
    public function __construct(
        private TicketRepository $ticketRepository,
        private Security $security
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('canceled_event_notifications', [$this, 'getCanceledEventNotifications']),
        ];
    }

    /**
     * @return array<int, array{evenement: \App\Entity\Evenement, tickets: string|int}>
     */
    public function getCanceledEventNotifications(): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return [];
        }

        return $this->ticketRepository->findCanceledEventNotificationsForUser($user);
    }
}
