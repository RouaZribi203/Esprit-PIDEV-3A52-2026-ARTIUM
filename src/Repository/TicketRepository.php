<?php

namespace App\Repository;

use App\Entity\Evenement;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\StatutEvenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    /**
     * @return array<int, array{evenement: \App\Entity\Evenement, tickets: string|int}>
     */
    public function findCanceledEventNotificationsForUser(User $user): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('e AS evenement', 'COUNT(t.id) AS tickets')
            ->from(Evenement::class, 'e')
            ->join('e.tickets', 't')
            ->where('t.user = :user')
            ->andWhere('e.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('statut', StatutEvenement::ANNULE)
            ->groupBy('e.id')
            ->orderBy('e.date_debut', 'DESC')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Ticket[] Returns an array of Ticket objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Ticket
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
