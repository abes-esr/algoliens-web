<?php

namespace App\Repository;

use App\Entity\Iln;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Iln|null find($id, $lockMode = null, $lockVersion = null)
 * @method Iln|null findOneBy(array $criteria, array $orderBy = null)
 * @method Iln[]    findAll()
 * @method Iln[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IlnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Iln::class);
    }

    // /**
    //  * @return Iln[] Returns an array of Iln objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Iln
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
