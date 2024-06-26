<?php

namespace App\Repository;

use App\Entity\Iln;
use App\Entity\Rcr;
use App\Entity\Record;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Rcr|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rcr|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rcr[]    findAll()
 * @method Rcr[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RcrRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rcr::class);
    }

    public function findByIln(Iln $iln) {
        return $this->findBy(['iln' => $iln], array('label' => 'ASC'));
    }

    public function updateStatsForRcr(Rcr $rcr): void {
        foreach ([Record::RECORD_TODO, Record::RECORD_VALIDATED, Record::RECORD_SKIPPED, Record::RECORD_FIXED_OUTSIDE] as $status) {
            $this->getEntityManager()->getConnection()->executeQuery("UPDATE `rcr` set records_status".$status." = (select count(*) from record where record.rcr_create_id = rcr.id and status = ".$status.") where rcr.id = ?", [$rcr->getId()]);
        }
    }

    // /**
    //  * @return Rcr[] Returns an array of Rcr objects
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
    public function findOneBySomeField($value): ?Rcr
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
