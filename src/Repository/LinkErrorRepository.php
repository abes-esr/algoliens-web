<?php

namespace App\Repository;

use App\Entity\LinkError;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method LinkError|null find($id, $lockMode = null, $lockVersion = null)
 * @method LinkError|null findOneBy(array $criteria, array $orderBy = null)
 * @method LinkError[]    findAll()
 * @method LinkError[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LinkErrorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LinkError::class);
    }

    // /**
    //  * @return LinkError[] Returns an array of LinkError objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?LinkError
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
