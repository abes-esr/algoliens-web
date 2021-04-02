<?php

namespace App\Repository;

use App\Entity\Iln;
use App\Entity\Record;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
        // Pour faire la disctinction par RCR, non utilisé pour le moment;
        $sql = "SELECT DATE_FORMAT(updated_at, '%Y') as year, DATE_FORMAT(updated_at, '%m') as month, DAYOFMONTH(updated_at) as day, status, count(distinct record.id) as nbrecords, count(distinct link_error.id) as nberrors FROM `record`, `link_error` where link_error.record_id = record.id and status in (" . Record::RECORD_VALIDATED . ", " . Record::RECORD_SKIPPED . ") and rcr_create_id in (select id from rcr where rcr.iln_id = ?) group by DATE_FORMAT(updated_at, '%Y'), month, day, status order by year, month, day";

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->bindValue(1, $iln->getId());
        $stmt->execute();

        $output = [];
        $results = $stmt->fetchAllAssociative();
        foreach ($results as $result) {

            if (!isset($output[$result["year"]])) {
                $output[$result["year"]] = [];
            }
            if (!isset($output[$result["year"]][$result["month"]])) {
                $output[$result["year"]][$result["month"]] = [];
            }
            if (!isset($output[$result["year"]][$result["month"]][$result["day"]])) {
                $output[$result["year"]][$result["month"]][$result["day"]] = [Record::RECORD_VALIDATED => ["nbrecords" => 0, "nberrors" => 0], Record::RECORD_SKIPPED => ["nbrecords" => 0, "nberrors" => 0]];
            }

            $output[$result["year"]][$result["month"]][$result["day"]][$result["status"]] = [
                "nbrecords" => $result["nbrecords"],
                "nberrors" => $result["nberrors"]
            ];
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
