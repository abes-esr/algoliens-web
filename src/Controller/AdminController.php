<?php

namespace App\Controller;

use App\Entity\BatchImport;
use App\Entity\Iln;
use App\Entity\Rcr;
use App\Entity\Record;
use App\Repository\BatchImportRepository;
use App\Repository\IlnRepository;
use App\Repository\RcrRepository;
use App\Repository\RecordRepository;
use App\Service\WsHarvester;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/adminavril")
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
    public function batchNew(Rcr $rcr, $batchType, KernelInterface $kernel, EventDispatcherInterface $eventDispatcher, LoggerInterface $logger, EntityManagerInterface $em) {

        $batchImport = new BatchImport($rcr, $batchType);
        $batchImport->setStatus(BatchImport::STATUS_RUNNING);
        $em->persist($batchImport);
        $em->flush();

        $eventDispatcher->addListener(KernelEvents::TERMINATE, function (Event $event) use ($logger, $batchImport, $em) {
            // Launch the job

            $wsHarvester = new WsHarvester($em);
            $wsHarvester->runNewBatchAlreadyCreated($batchImport, $logger);

            $logger->debug("G : ".$batchImport->getStartDate()->format("Y-m-d H:i:s"));

        });
        return $this->redirect($this->generateUrl("admin_rcr", ["ilnCode" => $rcr->getIln()->getCode(), "rcrCode" => $rcr->getCode()]));
    }

    /**
     * @Route("/iln/{ilnCode}/rcr/{rcrCode}/batch/{batchId}/{action}/{confirm?}", name="admin_batch_action")
     * @Entity("batchImport", expr="repository.findOneBy({'id': batchId})")
     */
    public function batch(EntityManagerInterface $em, BatchImport $batchImport, string $action, string $confirm = null) {
        if ($confirm == "confirm") {
            if ($action == "deleterecords") {
                $this->getDoctrine()->getRepository(Record::class)->deleteForBatch($batchImport);
                $this->getDoctrine()->getRepository(Rcr::class)->updateStats($batchImport->getRcr());

                $batchImport->setStatus(BatchImport::STATUS_CANCEL);
                $em->persist($batchImport);
            } elseif ($action == "deletebatch") {
                $em->remove($batchImport);
            }
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

    /**
     * @Route("/iln/{ilnCode}/import-rcr", name="admin_iln_populate_rcr")
     * @Entity("iln", expr="repository.findOneBy({'code': ilnCode})")
     */
    public function ilnPopulateWithRcr(Iln $iln, EntityManagerInterface $em, Request $request) {
        $rcrJson = file_get_contents("https://www.idref.fr/services/iln2rcr/".$iln->getNumber()."&format=text/json");
        $rcrArray = json_decode($rcrJson);

        $count = 0;
        foreach ($rcrArray->sudoc->query->result as $rcrDescription) {
            $rcr = new Rcr();
            $rcr->setCode($rcrDescription->library->rcr);
            $rcr->setLabel($rcrDescription->library->shortname);
            $rcr->setUpdated(new \DateTime());
            $rcr->setIln($iln);
            $rcr->setHarvested(0);
            $rcr->setActive(1);
            $em->persist($rcr);
            $count++;
        }
        $em->flush();

        $session = $request->getSession();
        $session->getFlashBag()->add('success', $count." RCR ajoutés.");

        return $this->redirect($this->generateUrl("admin"));
    }
}
