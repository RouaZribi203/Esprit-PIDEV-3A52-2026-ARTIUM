<?php

namespace App\Repository;

use App\Entity\Oeuvre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Oeuvre>
 */
class OeuvreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Oeuvre::class);
    }

    public function findByTitre(string $titre): array
    {
        return $this->createQueryBuilder('o')
            ->where('LOWER(o.titre) LIKE LOWER(:titre)')
            ->setParameter('titre', '%' . $titre . '%')
            ->orderBy('o.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByTitreWithSort(string $titre, string $sortBy = 'titre', string $sortOrder = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('o')
            ->where('LOWER(o.titre) LIKE LOWER(:titre)')
            ->setParameter('titre', '%' . $titre . '%');

        return $this->applySorting($qb, $sortBy, $sortOrder)->getQuery()->getResult();
    }

    public function findAllWithSort(string $sortBy = 'titre', string $sortOrder = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('o');
        return $this->applySorting($qb, $sortBy, $sortOrder)->getQuery()->getResult();
    }

    private function applySorting($qb, string $sortBy, string $sortOrder)
    {
        $sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

        switch ($sortBy) {
            case 'likes':
                $qb->leftJoin('o.likes', 'l')
                   ->groupBy('o.id')
                   ->orderBy('COUNT(l)', $sortOrder);
                break;
            case 'commentaires':
                $qb->leftJoin('o.commentaires', 'c')
                   ->groupBy('o.id')
                   ->orderBy('COUNT(c)', $sortOrder);
                break;
            case 'favoris':
                $qb->leftJoin('o.user_fav', 'uf')
                   ->groupBy('o.id')
                   ->orderBy('COUNT(uf)', $sortOrder);
                break;
            default:
                $qb->orderBy('o.titre', $sortOrder);
        }

        return $qb;
    }

    //    /**
    //     * @return Oeuvre[] Returns an array of Oeuvre objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Oeuvre
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
