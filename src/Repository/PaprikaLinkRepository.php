<?php

namespace App\Repository;

use App\Entity\PaprikaLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method PaprikaLink|null find($id, $lockMode = null, $lockVersion = null)
 * @method PaprikaLink|null findOneBy(array $criteria, array $orderBy = null)
 * @method PaprikaLink[]    findAll()
 * @method PaprikaLink[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaprikaLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaprikaLink::class);
    }

    // /**
    //  * @return PaprikaLink[] Returns an array of PaprikaLink objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PaprikaLink
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
