<?php


namespace App\Controller;


use App\Entity\Iln;
use App\Repository\IlnRepository;
use App\Repository\RecordRepository;
use App\Service\AbesLanguages;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class IlnController extends AbstractController
{

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
        return $this->render("iln/iln.html.twig", ["iln" => $iln]);
    }


    /**
     * @Route("/chantier/{code}-{secret}/stats", name="view_iln_stats")
     */
    public function ilnViewStats(Iln $iln)
    {
        $stats = $this->getDoctrine()->getRepository(Iln::class)->getStats($iln);
        return $this->render("iln/iln_stats.html.twig", ["iln" => $iln, "stats" => $stats]);
    }


    /**
     * @Route("/chantier/{code}-{secret}/langs", name="view_iln_langs")
     */
    public function ilnViewLangs(RecordRepository $recordRepository, Iln $iln, AbesLanguages $abesLanguages)
    {
        $langs = $recordRepository->getLangsForIln($iln);

        foreach ($langs as $id => $lang) {
            $label = $abesLanguages->getLabelForCode($lang["code"]);
            if (is_null($label)) {
                $label = "-";
            }
            $langs[$id]["langlabel"] = $label;
        }
        return $this->render("iln/langs.html.twig", ["langs" => $langs, "iln" => $iln]);
    }

    /**
     * @Route("/chantier/{code}-{secret}/reprises", name="view_iln_reprises")
     */
    public function ilnVienwReprises(RecordRepository $recordRepository, Iln $iln)
    {
        $skipReasons = $iln->getSkipReasons();
        return $this->render("iln/reprises.html.twig", ["iln" => $iln, "skipReaons" => $skipReasons]);
    }
}