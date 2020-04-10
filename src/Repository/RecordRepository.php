<?php

namespace App\Repository;

use App\Entity\BatchImport;
use App\Entity\Iln;
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

    public function getOneRandom(bool $winnie, Rcr $rcr = null, Iln $iln = null, string $lang = null, $unlocked = false)
    {
        if (is_null($rcr) && is_null($lang)) {
            print "Impossible de récupérer une notice (manque paramètre langue ou RCR)";
            exit;
        }

        # On commence par compter le nombre de résultats qui matchent notre demande
        $qb = $this->createQueryBuilder('l');

        $qb = $qb->where("l.locked is null and l.status = 0");
        if ($winnie === false) {
            $qb = $qb->andWhere("l.winnie = 0");
        }

        if (!is_null($rcr)) {
            $qb = $qb->andWhere("l.rcrCreate = :rcr");
            $qb = $qb->setParameter('rcr', $rcr);
        }

        if (!is_null($lang)) {
            $qb = $qb->andWhere("l.lang = :lang");
            $qb = $qb->setParameter('lang', $lang);
        }

        $countResults = $qb->select("COUNT(l)")->getQuery()->getSingleScalarResult();
        if (($countResults == 0) && ($unlocked === false)) {
            // On va essayer de débloquer les notices
            $this->unlockRecords();
            return $this->getOneRandom($winnie, $rcr, $iln, $lang, true);
        }

        $offset = rand(0, $countResults);

        $result = $qb->select("l")
            ->getQuery()
            ->setFirstResult($offset)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $result;
    }

    public function unlockRecords()
    {
        $em = $this->getEntityManager();
        $em->getConnection()->exec("UPDATE `record` set locked = null where locked is not null AND TIMEDIFF(now(), locked) > \"01:00:00\"");
    }

    public function forceUnlockRecordsForRcr(Rcr $rcr)
    {
        return $this->createQueryBuilder('l')
            ->update()
            ->set('l.locked', 'null')
            ->where("l.rcrCreate = :rcr and l.status = 0 and l.locked is not null")
            ->setParameter('rcr', $rcr)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getLockedRecordsForRcr(Rcr $rcr)
    {
        $result = $this->createQueryBuilder('l')
            ->where("l.rcrCreate = :rcr and l.status=0 and l.locked != ''")
            ->setParameter('rcr', $rcr)
            ->getQuery()
            ->getResult();
        return $result;
    }

    private function countByStatusForRcr(Rcr $rcr, int $status) {
        $countResults = $this->createQueryBuilder('l')
            ->select("COUNT(l)")
            ->where("l.status = :status and l.rcrCreate = :rcr")
            ->setParameter('rcr', $rcr)
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();

        return $countResults;
    }
    public function countCorrectedForRcr(Rcr $rcr) {
        return $this->countByStatusForRcr($rcr, Record::RECORD_VALIDATED );
    }

    public function countRepriseForRcr(Rcr $rcr) {
        return $this->countByStatusForRcr($rcr, Record::RECORD_SKIPPED);
    }

    public function findRepriseNeeded(Rcr $rcr) {
        return $this->createQueryBuilder('l')
            ->where("l.status = :status and l.rcrCreate = :rcr")
            ->setParameter('rcr', $rcr)
            ->setParameter('status', Record::RECORD_SKIPPED)
            ->getQuery()
            ->getResult();
    }

    public function deleteForBatch(BatchImport $batchImport)
    {
        $this->createQueryBuilder('l')
            ->delete()
            ->where('l.batchImport = :batchImport')
            ->setParameter('batchImport', $batchImport)
            ->getQuery()
            ->execute();
    }

    public function findMissingMarcForIln(Iln $iln)
    {
        return $this->createQueryBuilder('l')
            ->join("l.rcrCreate", "r")
            ->where("r.iln = :iln and l.marcBefore is null and l.status = 0")
            ->setParameter('iln', $iln)
            ->getQuery()
            ->getResult();
    }

    public function getLangsForIln(Iln $iln)
    {
        return $this->createQueryBuilder('rec')
            ->select("rec.lang as code, count(rec) as nb")
            ->join("rec.rcrCreate", "rcr")
            ->where("rec.status = 0 and rcr.iln = :iln and rec.lang is not null")
            ->setParameter('iln', $iln)
            ->groupBy("rec.lang")
            ->getQuery()
            ->getResult();
    }
}
