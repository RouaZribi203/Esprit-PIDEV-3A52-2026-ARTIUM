<?php

namespace App\Repository;

use App\Entity\Musique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Musique>
 */
class MusiqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Musique::class);
    }

    //    /**
    //     * @return Musique[] Returns an array of Musique objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Musique
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Find all music IDs and basic info only, without BLOBs
     * Returns lightweight data for listing pages
     * Use find($id) to get full entity with blobs when needed
     * 
     * @return array Array of objects with id, titre, description, date_creation, genre
     */
    public function findAllLightweight(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = '
            SELECT 
                m.id, 
                o.titre, 
                o.description, 
                o.date_creation,
                m.genre
            FROM musique m
            INNER JOIN oeuvre o ON m.id = o.id
            ORDER BY o.date_creation DESC
        ';
        
        $stmt = $conn->executeQuery($sql);
        return $stmt->fetchAllAssociative();
    }
}
