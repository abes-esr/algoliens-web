<?php

namespace App\Repository;

use App\Entity\Iln;
use App\Entity\Rcr;
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

    public function getStats(Iln $iln)
    {
        $rcrs = $this->getEntityManager()->getRepository(Rcr::class)->findByIln($iln);

        // Pour faire la disctinction par RCR, non utilisé pour le moment;
        $sql = "SELECT rcr_create_id, DATE_FORMAT(updated_at, '%Y') as year, DATE_FORMAT(updated_at, '%m') as month, DAYOFMONTH(updated_at) as day, status, count(*) as nbrecords FROM `record` where status in (1, 2) and rcr_create_id in (select id from rcr where rcr.iln_id = ?) group by rcr_create_id, year, month, day, status";
        $sql = "SELECT rcr_create_id, DATE_FORMAT(updated_at, '%Y') as year, DATE_FORMAT(updated_at, '%m') as month, DAYOFMONTH(updated_at) as day, status, count(*) as nbrecords FROM `record` where status in (1, 2) and rcr_create_id in (select id from rcr where rcr.iln_id = ?) group by rcr_create_id, DATE_FORMAT(updated_at, '%Y'), month, day, status order by year, month, day";

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->bindValue(1, $iln->getId());
        $stmt->execute();

        $output = [];
        $results = $stmt->fetchAll();
        foreach ($results as $result) {

            if (!isset($output[$result["year"]])) { $output[$result["year"]] = [];}
            if (!isset($output[$result["year"]][$result["month"]])) { $output[$result["year"]][$result["month"]] = [];}
            if (!isset($output[$result["year"]][$result["month"]][$result["day"]])) { $output[$result["year"]][$result["month"]][$result["day"]] = ["1" => 0, "2" => 0];}

            $output[$result["year"]][$result["month"]][$result["day"]][$result["status"]] += $result["nbrecords"];

        }
        return $output;
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
