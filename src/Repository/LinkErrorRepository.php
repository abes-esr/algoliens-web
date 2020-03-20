<?php

namespace App\Repository;

use App\Entity\LinkError;
use App\Entity\Rcr;
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

    public function findOneRandom(Rcr $rcr) {
        // $rows = $em->createQuery('SELECT COUNT(u.id) FROM AcmeUserBundle:User u')->getSingleScalarResult();
        $start = microtime(true);
        $countResults = $this->createQueryBuilder('l')
            ->select("COUNT(l)")
            ->where("l.status = 0 and l.rcrCreate = :rcrId")
            ->setParameter('rcrId', $rcr)
            ->getQuery()
            ->getSingleScalarResult();

        $offset = rand(0, $countResults);

        $result = $this->createQueryBuilder('l')
            ->where("l.status = 0 and l.rcrCreate = :rcrId")
            ->setParameter('rcrId', $rcr)
            ->getQuery()
            ->setFirstResult($offset)
            ->setMaxResults(1)
            ->getResult();
        return $result[0];
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
