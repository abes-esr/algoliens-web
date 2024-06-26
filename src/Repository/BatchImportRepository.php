<?php

namespace App\Repository;

use App\Entity\BatchImport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BatchImport|null find($id, $lockMode = null, $lockVersion = null)
 * @method BatchImport|null findOneBy(array $criteria, array $orderBy = null)
 * @method BatchImport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BatchImportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BatchImport::class);
    }

    public function findAll(): array
    {
        return $this->findBy(array(),
            array(
                'startDate' => 'DESC',
            )
        );
    }

    // /**
    //  * @return BatchImport[] Returns an array of BatchImport objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?BatchImport
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
