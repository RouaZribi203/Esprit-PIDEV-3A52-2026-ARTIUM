<?php

namespace App\Repository;

use App\Entity\Commentaire;
use Doctrine\DBAL\ParameterType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commentaire>
 */
class CommentaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commentaire::class);
    }

    public function findOwnershipDataById(int $id): ?array
    {
        return $this->createQueryBuilder('c')
            ->select('c.id AS id, IDENTITY(c.user) AS userId, IDENTITY(c.oeuvre) AS oeuvreId')
            ->andWhere('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteById(int $id): int
    {
        return $this->createQueryBuilder('c')
            ->delete()
            ->andWhere('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }

    public function updateTextIfOwnedByUser(int $id, int $userId, string $texte): int
    {
        return $this->getEntityManager()->getConnection()->executeStatement(
            'UPDATE commentaire SET texte = :texte WHERE id = :id AND user_id = :userId',
            [
                'texte' => $texte,
                'id' => $id,
                'userId' => $userId,
            ],
            [
                'id' => ParameterType::INTEGER,
                'userId' => ParameterType::INTEGER,
            ]
        );
    }

    //    /**
    //     * @return Commentaire[] Returns an array of Commentaire objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Commentaire
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
