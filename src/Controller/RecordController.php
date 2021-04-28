<?php


namespace App\Controller;


use App\Entity\Iln;
use App\Entity\Rcr;
use App\Entity\Record;
use App\Form\RecordType;
use App\Service\AbesLanguages;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Component\Routing\Annotation\Route;

class RecordController extends AbstractController
{
    private function getOneRecord(Request $request)
    {
        $route = $request->attributes->get("_route");
        $winnie = $request->getSession()->get("winnie", false);
        $iln = $request->attributes->get("iln");

        $lang = null;
        $rcr = null;
        if ($route == "view_record_lang") {
            $lang = $request->attributes->get("lang");
        } elseif (($route == "view_record_rcr") || ($route == "view_record_permalink")) {
            $rcr = $request->attributes->get("rcr");
        } else {
            dd($request);
        }

        if ($request->getMethod() == "GET") {
            $record = $this->getDoctrine()->getRepository(Record::class)->getOneRandom($winnie, $rcr, $iln, $lang);
        } else {
            // Le formulaire a été validé, on se contente de créer un
            // record vide avec le ou les paramètres qui nous intéressent
            // TODO: améliorer cette manière de gérer les formulaires

            $record = new Record();
            if ($route == "view_record_lang") {
                $i = 1;
            } else {
                $record->setRcrCreate($rcr);
            }
        }
        return $record;
    }

    private function processForm(FormInterface $form, Request $request, EntityManagerInterface $em, string $responseTemplate, array $responseParams)
    {
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
                    $session->getFlashBag()->add('success', "Correction de la notice n°" . $record->getPpn() . " enregistrée, elle ne sera plus proposée par cette interface.");
                } elseif ($submitButton->getName() == "skip") {
                    $skipReason = $recordForm->getSkipReason();
                    $record->setStatus(Record::RECORD_SKIPPED);
                    $record->setComment($recordForm->getComment());
                    $record->setSkipReason($skipReason);
                    // On ajoute 1 pour tenir compte de la notice en cours

                    $session = $request->getSession();
                    $session->getFlashBag()->add('success', "Cette notice ne sera plus proposée. Elle sera listée dans celles à reprendre document en main.");
                }
                $em->persist($record);
                $em->flush();

                // On met à jour les stats du RCR
                $em->getRepository(Rcr::class)->updateStatsForRcr($record->getRcrCreate());

                return true;
            } else {
                $session = $request->getSession();
                $session->getFlashBag()->add('danger', "<strong>Attention</strong> : l'enregistrement du formulaire a provoqué une erreur (<i>Timeout</i>). Merci de le valider à nouveau pour que la modification soit bien prise en compte.");

                $responseParams["form"] = $form->createView();
                $responseParams["record"] = $record;
                return $this->render($responseTemplate,
                    $responseParams
                );
            }
        }
        return false;
    }

    private function viewRecord(Record $record = null, Iln $iln, EntityManagerInterface $em, Request $request, RedirectResponse $redirectResponse, string $responseTemplate, array $responseParams)
    {
        $form = $this->createForm(RecordType::class, $record, ["skip_reasons" => $iln->getSkipReasons(), "skip_reason_default" => $iln->getDefaultSkipReason()]);

        $formProcess = $this->processForm($form, $request, $em, $responseTemplate, $responseParams);
        if ($formProcess === true) {
            return $redirectResponse;
        } elseif (gettype($formProcess) == "object" && get_class($formProcess) == "Symfony\Component\HttpFoundation\Response") {
            return $formProcess;
        }

        if (!is_null($record)) {
            $record->setLocked();
            $em->persist($record);
            $em->flush();
        }

        $responseParams["form"] = $form->createView();
        return $this->render($responseTemplate,
            $responseParams
        );
    }

    /**
     * @Route("/chantier/{ilnCode}-{ilnSecret}/lang/{lang}", name="view_record_lang")
     * @Entity("iln", expr="repository.findOneBy({'code': ilnCode, 'secret': ilnSecret})")
     */
    public function viewForLang(Iln $iln, string $lang, EntityManagerInterface $em, Request $request, AbesLanguages $abesLanguages)
    {
        $record = $this->getOneRecord($request);

        $redirectResponse = $this->redirect($this->generateUrl("view_record_lang", ['ilnCode' => $iln->getCode(), 'ilnSecret' => $iln->getSecret(), 'lang' => $lang]));

        $responseTemplate = "record/record_for_lang.html.twig";
        $responseParams = [
            "iln" => $iln,
            "record" => $record,
            "empty" => null,
            "langCode" => $lang,
            "langLabel" => $abesLanguages->getLabelForCode($lang),
            "rcr" => $record->getRcrCreate()
        ];

        return $this->viewRecord($record, $iln, $em, $request, $redirectResponse, $responseTemplate, $responseParams);
    }

    /**
     * @Route("/chantier/{ilnCode}-{ilnSecret}/rcr/{rcrCode}", name="view_record_rcr")
     * @Entity("iln", expr="repository.findOneBy({'code': ilnCode})")
     * @Entity("rcr", expr="repository.findOneBy({'code': rcrCode})")
     */
    public function viewForRcr(Iln $iln, Rcr $rcr, EntityManagerInterface $em, Request $request)
    {
        $record = $this->getOneRecord($request);
        $redirectResponse = $this->redirect($this->generateUrl("view_record_rcr", ['ilnCode' => $iln->getCode(), 'ilnSecret' => $iln->getSecret(), 'rcrCode' => $rcr->getCode()]));

        $responseTemplate = "record/record_for_rcr.html.twig";
        $responseParams = [
            "iln" => $iln,
            "record" => $record,
            "rcr" => $rcr
        ];


        if (is_null($record)) {
            $responseParams["lockedRecords"] = $this->getDoctrine()->getRepository(Record::class)->getLockedRecordsForRcr($rcr);
            return $this->render($responseTemplate,
                $responseParams
            );
        }

        return $this->viewRecord($record, $iln, $em, $request, $redirectResponse, $responseTemplate, $responseParams);
    }


    /**
     * @Route("/chantier/{ilnCode}-{ilnSecret}/rcr/{rcrCode}/{ppn}-{idRecord}", name="view_record_permalink")
     * @Entity("iln", expr="repository.findOneBy({'code': ilnCode})")
     * @Entity("rcr", expr="repository.findOneBy({'code': rcrCode})")
     * @Entity("record", expr="repository.find(idRecord)")
     */
    public function viewPermalink(Iln $iln, Rcr $rcr, Record $record, EntityManagerInterface $em, Request $request)
    {
        $redirectResponse = $this->redirect($this->generateUrl("view_record_rcr", ['ilnCode' => $iln->getCode(), 'ilnSecret' => $iln->getSecret(), 'rcrCode' => $rcr->getCode()]));

        $responseTemplate = "record/record_for_rcr.html.twig";
        $responseParams = [
            "iln" => $iln,
            "record" => $record,
            "rcr" => $rcr,
            "permalink" => true
        ];


        if (is_null($record)) {
            $responseParams["lockedRecords"] = $this->getDoctrine()->getRepository(Record::class)->getLockedRecordsForRcr($rcr);
            return $this->render($responseTemplate,
                $responseParams
            );
        }
        return $this->viewRecord($record, $iln, $em, $request, $redirectResponse, $responseTemplate, $responseParams);
    }
}