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
     * Recherche les utilisateurs par nom et prénom combinés (partiel)
     *
     * @param string|null $nomPrenom
     * @param string $order 'ASC' ou 'DESC'
     * @return User[]
     */
    public function searchByNomPrenomQuery(?string $nomPrenom, string $order = 'ASC')
    {
        $qb = $this->createQueryBuilder('u');
        if ($nomPrenom && strpos(trim($nomPrenom), ' ') !== false) {
            $qb->andWhere("CONCAT(LOWER(u.nom), ' ', LOWER(u.prenom)) LIKE :nomPrenom")
               ->setParameter('nomPrenom', '%' . strtolower($nomPrenom) . '%');
        } elseif ($nomPrenom) {
            // Si nomPrenom ne contient pas d'espace, retourne aucun résultat
            $qb->andWhere('1=0');
        }
        // Si nomPrenom est null ou vide, ne filtre pas
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
