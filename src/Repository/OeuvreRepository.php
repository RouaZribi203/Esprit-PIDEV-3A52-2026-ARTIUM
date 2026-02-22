<?php

namespace App\Repository;

use App\Entity\Oeuvre;
use App\Entity\User;
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

    public function findByTypeWithSort($type, string $sortBy = 'titre', string $sortOrder = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.type = :type')
            ->setParameter('type', $type);
        
        return $this->applySorting($qb, $sortBy, $sortOrder)->getQuery()->getResult();
    }

    public function findDistinctCommentedByUser(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->distinct()
            ->innerJoin('o.commentaires', 'c')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findRecommendedForUser(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->distinct()
            ->leftJoin('o.commentaires', 'c', 'WITH', 'c.user = :user')
            ->leftJoin('o.likes', 'l', 'WITH', 'l.user = :user AND l.liked = true')
            ->leftJoin('o.user_fav', 'uf', 'WITH', 'uf.id = :userId')
            ->where('c.id IS NOT NULL OR l.id IS NOT NULL OR uf.id IS NOT NULL')
            ->setParameter('user', $user)
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getResult();
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
    public function findByTypes(array $types, int $limit = 10): array
    {
        if (empty($types)) return [];
        return $this->createQueryBuilder('o')
            ->where('o.type IN (:types)')
            ->setParameter('types', $types)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
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
