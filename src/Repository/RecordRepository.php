<?php

namespace App\Repository;

use App\Entity\Rcr;
use App\Entity\Record;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Record|null find($id, $lockMode = null, $lockVersion = null)
 * @method Record|null findOneBy(array $criteria, array $orderBy = null)
 * @method Record[]    findAll()
 * @method Record[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Record::class);
    }

    public function findOneRandom(Rcr $rcr) {
        $countResults = $this->createQueryBuilder('l')
            ->select("COUNT(l)")
            ->where("l.locked is null and l.status = 0 and l.rcrCreate = :rcr")
            ->setParameter('rcr', $rcr)
            ->getQuery()
            ->getSingleScalarResult();

        $offset = rand(0, $countResults);
        print "Count results : ".$countResults."<br/>";
        $result = $this->createQueryBuilder('l')
            ->where("l.locked is null and l.status = 0 and l.rcrCreate = :rcr")
            ->setParameter('rcr', $rcr)
            ->getQuery()
            ->setFirstResult($offset)
            ->setMaxResults(1)
            ->getResult();
        return $result[0];
    }

    // /**
    //  * @return Record[] Returns an array of Record objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Record
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
