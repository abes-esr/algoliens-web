<?php

namespace App\Controller;

use App\Entity\BatchImport;
use App\Entity\Iln;
use App\Entity\Rcr;
use App\Entity\Record;
use App\Repository\BatchImportRepository;
use App\Repository\IlnRepository;
use App\Service\WsHarvester;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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
    public function rcr(Rcr $rcr)
    {
        return $this->render('admin/rcr.html.twig', [
            'rcr' => $rcr
        ]);
    }

    /**
     * @Route("/iln/{ilnCode}/rcr/{rcrCode}/batch/new/{batchType}/{batchId?}", name="admin_batch_new")
     * @Entity("rcr", expr="repository.findOneBy({'code': rcrCode})")
     * @Entity("batchImport", expr="repository.findOneBy({'id': batchId})")
     */
    public function batchNew(Rcr $rcr, $batchType, EventDispatcherInterface $eventDispatcher, LoggerInterface $logger, EntityManagerInterface $em, BatchImport $batchImport = null)
    {
        if (!is_null($batchImport)) {
            $this->getDoctrine()->getRepository(Record::class)->deactivateForBatch($batchImport);
            $batchImport->setStatus(BatchImport::STATUS_RUNNING);
            $batchImport->setStartDate(new DateTime());
            $batchImport->setEndDate(null);
        } else {
            $batchImport = new BatchImport($rcr, $batchType);
            $batchImport->setStatus(BatchImport::STATUS_RUNNING);
        }

        $em->persist($batchImport);
        $em->flush();

        $eventDispatcher->addListener(KernelEvents::TERMINATE, function (Event $event) use ($logger, $batchImport, $em) {
            // Launch the job
            $wsHarvester = new WsHarvester($em);
            $wsHarvester->runNewBatchAlreadyCreated($batchImport, $logger);
            $em->getRepository(Rcr::class)->updateStatsForRcr($batchImport->getRcr());
        });
        return $this->redirect($this->generateUrl("admin_rcr", ["ilnCode" => $rcr->getIln()->getCode(), "rcrCode" => $rcr->getCode()]));
    }

    /**
     * @Route("/iln/{ilnCode}/rcr/{rcrCode}/batch/{batchId}/{action}/{confirm?}", name="admin_batch_action")
     * @Entity("batchImport", expr="repository.findOneBy({'id': batchId})")
     */
    public function batch(EntityManagerInterface $em, BatchImport $batchImport, string $action, string $confirm = null)
    {
        if ($confirm == "confirm") {
            if ($action == "deleterecords") {
                $this->getDoctrine()->getRepository(Record::class)->deleteForBatch($batchImport);
                $this->getDoctrine()->getRepository(Rcr::class)->updateStatsForRcr($batchImport->getRcr());

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
    public function ilnPopulateWithRcr(Iln $iln, EntityManagerInterface $em, Request $request, HttpClientInterface $client)
    {
        $urlWs = "https://www.idref.fr/services/iln2rcr/" . $iln->getNumber() . "&format=text/json";
        $response = $client->request("GET", $urlWs);
        $rcrArray = json_decode($response->getContent());

        $rcrRepository = $em->getRepository(Rcr::class);

        $count = 0;
        foreach ($rcrArray->sudoc->query->result as $rcrDescription) {
            $library = null;
            if (!isset($rcrDescription->library)) {
                // Dans le cas d'un RCR unique pour un ILN, on n'a plus la même structure
                // Exemple pour l'ILN 129
                $library = $rcrDescription;
            } else {
                $library = $rcrDescription->library;
            }

            $rcr = $rcrRepository->findOneBy(["code" => $library->rcr]);
            if (!$rcr) {
                $rcr = new Rcr();

                $rcr->setCode($library->rcr);
                $rcr->setLabel($library->shortname);
                $rcr->setUpdated(new DateTime());
                $rcr->setIln($iln);
                $rcr->setHarvested(0);
                $rcr->setActive(1);
                $em->persist($rcr);
                $count++;
            }
        }
        $em->flush();

        $session = $request->getSession();
        $session->getFlashBag()->add('success', $count . " RCR ajoutés.");

        return $this->redirect($this->generateUrl("admin_iln", ["code" => $iln->getCode()]));
    }

    /**
     * @Route("/fix-reprises", name="admin_fix_reprises")
     */
    public function fixReprises(EntityManagerInterface $em, Request $request)
    {
        $rcrs = $em->getRepository(Rcr::class)->findAll();

        /** @var Rcr $rcr */
        foreach ($rcrs as $rcr) {
            $em->getRepository(Rcr::class)->updateStatsForRcr($rcr);
        }

//        $reprisesForIln = $em->getRepository(Record::class)->countRepriseForRcrs();
//        $count = 0;
//        foreach ($reprisesForIln as $reprise) {
//            /** @var Rcr $reprise ["rcr"] */
//            if ($reprise["rcr"]->getNumberOfRecordsReprise() != $reprise["count"]) {
//                $reprise["rcr"]->setNumberOfRecordsReprise($reprise["count"]);
//                $count++;
//                $em->persist($reprise["rcr"]);
//            }
//        }
        $em->flush();
        $session = $request->getSession();
        $session->getFlashBag()->add('success', "RCR corrigé(s).");

        return $this->redirect(
            $this->generateUrl("admin")
        );
    }
}
