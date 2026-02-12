<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    /**
     * Recherche les utilisateurs par nom (partiel) et trie par nom (ordre A-Z ou Z-A)
     *
     * @param string|null $nom
     * @param string $order 'ASC' ou 'DESC'
     * @return User[]
     */
    /**
     * Retourne un Query pour pagination
     */
    public function searchByNomQuery(?string $nom, string $order = 'ASC')
    {
        $qb = $this->createQueryBuilder('u');
        if ($nom) {
            $qb->andWhere('LOWER(u.nom) LIKE :nom')
               ->setParameter('nom', '%' . strtolower($nom) . '%');
        }
        $qb->orderBy('u.nom', $order)
           ->addOrderBy('u.prenom', $order);
        return $qb->getQuery();
    }
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
