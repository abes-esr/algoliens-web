<?php

namespace App\Controller;

use App\Entity\BatchImport;
use App\Entity\Iln;
use App\Entity\Rcr;
use App\Repository\BatchImportRepository;
use App\Repository\IlnRepository;
use App\Repository\RcrRepository;
use App\Repository\RecordRepository;
use App\Service\WsHarvester;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin")
 */

class AdminController extends AbstractController
{
    /**
     * @Route("/", name="admin")
     */
    public function index(BatchImportRepository $batchImportRepository, IlnRepository $ilnRepository)
    {
        $batchImports = $batchImportRepository->findAll();

        $ilns = $ilnRepository->findAll();

        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
            'imports' => $batchImports,
            'ilns' => $ilns
        ]);
    }

    /**
     * @Route("/iln/{code}", name="admin_iln")
     */
    public function iln(Iln $iln)
    {
        return $this->render('admin/iln.html.twig', [
            'iln' => $iln
        ]);
    }

    /**
     * @Route("/iln/{ilnCode}/rcr/{rcrCode}", name="admin_rcr")
     * @Entity("iln", expr="repository.findOneBy({'code': ilnCode})")
     * @Entity("rcr", expr="repository.findOneBy({'code': rcrCode})")
     */
    public function rcr(Iln $iln, Rcr $rcr)
    {
        return $this->render('admin/rcr.html.twig', [
            'rcr' => $rcr
        ]);
    }

    /**
     * @Route("/iln/{ilnCode}/rcr/{rcrCode}/batch/new/{batchType}", name="admin_batch_new")
     * @Entity("rcr", expr="repository.findOneBy({'code': rcrCode})")
     */
    public function batchNew(Rcr $rcr, $batchType, WsHarvester $wsHarvester) {
//        $batch = $wsHarvester->runNewBatch($rcr, $batchType);
    }

    /**
     * @Route("/iln/{ilnCode}/rcr/{rcrCode}/batch/{batchId}/{action}/{confirm?}", name="admin_batch_action")
     * @Entity("batchImport", expr="repository.findOneBy({'id': batchId})")
     */
    public function batch(EntityManagerInterface $em, RcrRepository $rcrRepository, RecordRepository $recordRepository, BatchImport $batchImport, string $action, string $confirm = null) {
        if ($confirm == "confirm") {
            $recordRepository->deleteForBatch($batchImport);
            $rcrRepository->updateStats($batchImport->getRcr());
            $batchImport->setStatus(BatchImport::STATUS_CANCEL);
            $em->persist($batchImport);
            $em->flush();
            return $this->redirect(
                $this->generateUrl("admin_rcr", ["ilnCode" => $batchImport->getIlnCode(), "rcrCode" => $batchImport->getRcr()->getCode()])
            );
        }
        return $this->render('admin/batch.html.twig', [
            'batchImport' => $batchImport,
            'action' => $action,
            'confirm' => $confirm
        ]);
    }
}
