<?php

namespace App\Repository;

use App\Entity\LinkError;
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

    public function findOneRandomNoWinnie(Rcr $rcr) {
        $countResults = $this->createQueryBuilder('l')
            ->select("COUNT(l)")
            ->where("l.winnie = 0 and l.locked is null and l.status = 0 and l.rcrCreate = :rcr")
            ->setParameter('rcr', $rcr)
            ->getQuery()
            ->getSingleScalarResult();

        $offset = rand(0, $countResults);

        $result = $this->createQueryBuilder('l')
            ->where("l.winnie = 0 and l.locked is null and l.status = 0 and l.rcrCreate = :rcr")
            ->setParameter('rcr', $rcr)
            ->getQuery()
            ->setFirstResult($offset)
            ->setMaxResults(1)
            ->getResult();

        if (sizeof($result) == 0) {
            return null;
        }
        return $result[0];
    }

    public function findOneRandom(Rcr $rcr) {
        $countResults = $this->createQueryBuilder('l')
            ->select("COUNT(l)")
            ->where("l.locked is null and l.status = 0 and l.rcrCreate = :rcr")
            ->setParameter('rcr', $rcr)
            ->getQuery()
            ->getSingleScalarResult();
        $offset = rand(0, $countResults);

        $result = $this->createQueryBuilder('l')
            ->where("l.locked is null and l.status = 0 and l.rcrCreate = :rcr")
            ->setParameter('rcr', $rcr)
            ->getQuery()
            ->setFirstResult($offset)
            ->setMaxResults(1)
            ->getResult();

        if (sizeof($result) == 0) {
            return null;
        }
        return $result[0];
    }

    public function unlockRecords() {
        $em = $this->getEntityManager();
        $em->getConnection()->exec("UPDATE `record` set locked = null where locked is not null AND TIMEDIFF(now(), locked) > \"00:00:00\"");
    }

    public function countCorrectedForRcr(Rcr $rcr) {
        $countResults = $this->createQueryBuilder('l')
            ->select("COUNT(l)")
            ->where("l.status = 1 and l.rcrCreate = :rcr")
            ->setParameter('rcr', $rcr)
            ->getQuery()
            ->getSingleScalarResult();

        return $countResults;
    }

    public function findRepriseNeeded(Rcr $rcr) {
        return $this->createQueryBuilder('l')
            ->where("l.comment != '' and l.rcrCreate = :rcr")
            ->setParameter('rcr', $rcr)
            ->getQuery()
            ->getResult();
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
