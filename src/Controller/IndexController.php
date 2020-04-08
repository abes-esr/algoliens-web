<?php

namespace App\Controller;

use App\Entity\Iln;
use App\Entity\LinkError;
use App\Entity\Rcr;
use App\Entity\Record;
use App\Form\RecordType;
use App\Repository\IlnRepository;
use App\Repository\RecordRepository;
use Doctrine\ORM\EntityManagerInterface;
use function PHPSTORM_META\type;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index()
    {
        return $this->render("index.html.twig");
    }

    /**
     * @Route("/chantier/{code}-{secret}/params", name="settings")
     */
    public function settings(Request $request, $code, $secret)
    {
        $form = $this->createFormBuilder();
        $session = $request->getSession();
        if ( ($session->has("winnie")) && ($session->get("winnie") == "1") )
        {
            $form = $form->add('winnie', CheckboxType::class,
            [
                "label" => "WinbiW est installé sur mon poste",
                "attr" => ["checked" => "checked"],
                "required" => false
            ]);
        } else {
            $form = $form->add('winnie', CheckboxType::class,
                [
                    "label" => "WinbiW est installé sur mon poste",
                    "required" => false
                ]);
        }

        $form = $form->add('save', SubmitType::class, ["label" => "Enregistrer"])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $settingsData = $form->getData();
            $session->set("winnie", $settingsData["winnie"]);
            return $this->redirect($this->generateUrl("view_iln", ["code" => $code, "secret" => $secret]));
        }

        return $this->render("settings.html.twig", ["form" => $form->createView()]);
    }

    /**
     * @Route("/iln/015", name="view_iln_legacy")
     */
    public function ilnViewLegacy(IlnRepository $ilnRepository)
    {
        $iln = $ilnRepository->findOneBy(["number" => 15]);
        return $this->redirect($this->generateUrl("view_iln", ["code" => $iln->getCode(), "secret" => $iln->getSecret()]));
    }

    /**
     * @Route("/chantier/{code}-{secret}", name="view_iln")
     */
    public function ilnView(Request $request, Iln $iln)
    {
        $session = $request->getSession();
        if (!$session->has("winnie")) {
            return $this->redirect($this->generateUrl("settings", ["code" => $iln->getCode(), "secret" => $iln->getSecret()]));
        }
        return $this->render("iln.html.twig", ["iln" => $iln]);
    }

    /**
     * @Route("/chantier/{ilnCode}-{ilnSecret}/rcr/{rcrCode}/unlock", name="force_unlock")
     * @Entity("iln", expr="repository.findOneBy({'code': ilnCode})")
     * @Entity("rcr", expr="repository.findOneBy({'code': rcrCode})")
     */
    public function forceUnlock(Rcr $rcr, Iln $iln, RecordRepository $recordRepository, Request $request)
    {
        $unlockedRecords = $recordRepository->forceUnlockRecordsForRcr($rcr);
        $session = $request->getSession();
        $session->getFlashBag()->add('success', $unlockedRecords." notices libérées.");
        return $this->redirect($this->generateUrl('view_rcr', ['ilnCode' => $iln->getCode(), 'rcrCode' => $rcr->getCode(), "secret" => $iln->getSecret()]));
    }



    private function getOneRecord(Request $request, Rcr $rcr, ?string $ppn) {
        $session = $request->getSession();
        if (is_null($ppn)) {
            if ($session->get("winnie")) {
                $record = $this->getDoctrine()->getRepository(Record::class)->findOneRandom($rcr);
            } else {
                $record = $this->getDoctrine()->getRepository(Record::class)->findOneRandomNoWinnie($rcr);
            }
        } else {
            $record = $this->getDoctrine()->getRepository(Record::class)->findOneBy(["ppn" => $ppn]);
        }
        return $record;
    }

    /**
     * @Route("/iln/{ilnCode}/rcr/{rcrCode}/reprise", name="view_rcr_reprise")
     * @Entity("iln", expr="repository.findOneBy({'code': ilnCode})")
     * @Entity("rcr", expr="repository.findOneBy({'code': rcrCode})")
     */
    public function rcrViewReprise(Iln $iln, Rcr $rcr, EntityManagerInterface $em, Request $request)
    {
        $records = $this->getDoctrine()->getRepository(Record::class)->findRepriseNeeded($rcr);
        return $this->render("rcr_view_reprise.html.twig", ["iln" => $iln, "records" => $records]);
    }


    /**
     * @Route("/chantier/{ilnCode}-{secret}/rcr/{rcrCode}/{ppn?}", name="view_rcr")
     * @Entity("iln", expr="repository.findOneBy({'code': ilnCode})")
     * @Entity("rcr", expr="repository.findOneBy({'code': rcrCode})")
     */
    public function rcrView(Iln $iln, Rcr $rcr, ?string $ppn, EntityManagerInterface $em, Request $request)
    {
        if ($request->getMethod() == "GET") {
            $record = $this->getOneRecord($request, $rcr, $ppn);
            // TODO : traiter base vide
            if (is_null($record)) {
                // On essaie d'abord de libérer toutes les notices "lockées"
                $this->getDoctrine()->getRepository(Record::class)->unlockRecords();

                $record = $this->getOneRecord($request, $rcr, $ppn);

                if (is_null($record)) {
                    // On va vérifier combien il y a des notices "bloquées".
                    $lockedRecords = $this->getDoctrine()->getRepository(Record::class)->getLockedRecords($rcr);
                    return $this->render("record.html.twig",
                        [
                            "iln" => $iln,
                            "rcr" => $rcr,
                            "empty" => 1,
                            "lockedRecords" => $lockedRecords
                        ]
                    );
                }
            }
        } else {
            $record = new Record();
            $record->setRcrCreate($rcr);
        }

        $form = $this->createForm(RecordType::class, $record);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $submitButton = $form->getClickedButton();
            $recordForm = $form->getData();
            $id = $recordForm->getId();
            $record = $this->getDoctrine()->getRepository(Record::class)->find($id);

            if ($form->isValid()) {
                if ($submitButton->getName() == "validate") {
                    // On va mettre à jour
                    $record->setStatus(1);
                    $session = $request->getSession();
                    $session->getFlashBag()->add('success', "Correction de la notice n°".$record->getPpn()." enregistrée, elle ne sera plus proposée par cette interface.");
                    // On ajoute 1 pour tenir compte de la notice en cours
                    $countCorrected = 1 + $em->getRepository(Record::class)->countCorrectedForRcr($record->getRcrCreate());
                    $record->getRcrCreate()->setNumberOfRecordsCorrected($countCorrected);
                } elseif ($submitButton->getName() == "skip") {
                    $skipReason = $recordForm->getSkipReason();
                    $record->setStatus(Record::RECORD_SKIPPED);
                    $record->setComment($recordForm->getComment());
                    $record->setSkipReason($skipReason);
                    // On ajoute 1 pour tenir compte de la notice en cours
                    $countReprise = 1 + $em->getRepository(Record::class)->countRepriseForRcr($record->getRcrCreate());
                    $record->getRcrCreate()->setNumberOfRecordsReprise($countReprise);

                    $session = $request->getSession();
                    $session->getFlashBag()->add('success', "Cette notice ne sera plus proposée. Elle sera listée dans celles à reprendre document en main.");
                }

                $em->persist($record);
                $em->flush();

                return $this->redirect($this->generateUrl("view_rcr", ['ilnCode' => $iln->getCode(), 'rcrCode' => $rcr->getCode(), 'secret' => $iln->getSecret()]));
            } else {
                $session = $request->getSession();
                $session->getFlashBag()->add('danger', "<strong>Attention</strong> : l'enregistrement du formulaire a provoqué une erreur (<i>Timeout</i>). Merci de le valider à nouveau pour que la modification soit bien prise en compte.");

                return $this->render("record.html.twig",
                    [
                        "iln" => $iln,
                        "rcr" => $rcr,
                        "record" => $record,
                        "empty" => null,
                        "form" => $form->createView()
                    ]
                );
            }
        }

        $record->setLocked();

        $em->persist($record);
        $em->flush();

        return $this->render("record.html.twig",
            [
                "iln" => $iln,
                "rcr" => $rcr,
                "record" => $record,
                "empty" => null,
                "form" => $form->createView()
            ]
        );
    }

    /**
     * @Route("/iln/{code}/stats", name="view_iln_stats")
     */
    public function ilnViewStats(Iln $iln)
    {
        $stats = $this->getDoctrine()->getRepository(Iln::class)->getStats($iln);
        return $this->render("iln_stats.html.twig", ["iln" => $iln, "stats" => $stats]);
    }
}
