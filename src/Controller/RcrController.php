<?php


namespace App\Controller;


use App\Entity\Iln;
use App\Entity\Rcr;
use App\Entity\Record;
use App\Repository\RecordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class RcrController extends AbstractController
{
    /**
     * @Route("/chantier/{ilnCode}-{ilnSecret}/rcr/{rcrCode}/unlock", name="force_unlock")
     * @Entity("iln", expr="repository.findOneBy({'code': ilnCode})")
     * @Entity("rcr", expr="repository.findOneBy({'code': rcrCode})")
     */
    public function forceUnlock(Rcr $rcr, Iln $iln, RecordRepository $recordRepository, Request $request)
    {
        $unlockedRecords = $recordRepository->forceUnlockRecordsForRcr($rcr);
        $session = $request->getSession();
        $session->getFlashBag()->add('success', $unlockedRecords . " notices libérées.");
        return $this->redirect($this->generateUrl('view_record_rcr', ['ilnCode' => $iln->getCode(), 'rcrCode' => $rcr->getCode(), "ilnSecret" => $iln->getSecret()]));
    }


    /**
     * @Route("/iln/{ilnCode}/rcr/{rcrCode}/reprise", name="view_rcr_reprise")
     * @Entity("iln", expr="repository.findOneBy({'code': ilnCode})")
     * @Entity("rcr", expr="repository.findOneBy({'code': rcrCode})")
     */
    public function rcrViewReprise(Iln $iln, Rcr $rcr, EntityManagerInterface $em, Request $request)
    {
        $records = $this->getDoctrine()->getRepository(Record::class)->findRepriseNeeded($rcr);
        $recordsByReason = array();
        $recordsByReason[0] = ["skipReason" => null, "records" => []];
        foreach ($iln->getSkipReasons() as $skipReason) {
            $recordsByReason[$skipReason->getId()] = ["skipReason" => $skipReason, "records" => []];
        }

        foreach ($records as $record) {
            if (is_null($record->getSkipReason())) {
                $recordsByReason[0]["records"][] = $record;
            } else {
                $recordsByReason[$record->getSkipReason()->getId()]["records"][] = $record;
            }
        }
        return $this->render("rcr/view_reprise.html.twig", ["iln" => $iln, "recordsByReason" => $recordsByReason]);
    }
}