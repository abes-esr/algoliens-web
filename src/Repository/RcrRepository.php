<?php

namespace App\Repository;

use App\Entity\Iln;
use App\Entity\Rcr;
use App\Entity\Record;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

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

    public function updateStats(Rcr $rcr) {
        $recordRepository = $this->getEntityManager()->getRepository(Record::class);

        $countRecords = sizeof($recordRepository->findBy(['rcrCreate' => $rcr]));
        $countRecordsCorrected = sizeof($recordRepository->findBy(['rcrCreate' => $rcr, 'status' => Record::RECORD_VALIDATED]));
        $countRecordsCorrectedOutside = sizeof($recordRepository->findBy(['rcrCreate' => $rcr, 'status' => Record::RECORD_FIXED_OUTSIDE]));
        $countRecordsReprise = sizeof($recordRepository->findBy(['rcrCreate' => $rcr, 'status' => Record::RECORD_SKIPPED]));

        $q = $this->createQueryBuilder('l')
            ->update()
            ->set('l.numberOfRecords', ':countRecords')
            ->set('l.numberOfRecordsCorrected', ':countRecordsCorrected')
            ->set('l.numberOfRecordsReprise', ':countRecordsReprise')
            ->set('l.numberOfRecordsFixedOutside', ':countRecordsCorrectedOutside')
            ->setParameter('countRecords', $countRecords)
            ->setParameter('countRecordsCorrected', $countRecordsCorrected)
            ->setParameter('countRecordsReprise', $countRecordsReprise)
            ->setParameter('countRecordsCorrectedOutside', $countRecordsCorrectedOutside)
            ->where('l.id = :rcrId')
            ->setParameter('rcrId', $rcr->getId())
            ->getQuery();
        $count = $q->execute();
        return $count;

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
