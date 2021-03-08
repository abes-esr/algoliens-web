<?php

namespace App\Repository;

use App\Entity\SkipReason;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SkipReason|null find($id, $lockMode = null, $lockVersion = null)
 * @method SkipReason|null findOneBy(array $criteria, array $orderBy = null)
 * @method SkipReason[]    findAll()
 * @method SkipReason[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SkipReasonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SkipReason::class);
    }

    // /**
    //  * @return SkipReason[] Returns an array of SkipReason objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SkipReason
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
