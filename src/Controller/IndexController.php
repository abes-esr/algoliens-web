<?php

namespace App\Controller;

use App\Entity\Iln;
use App\Entity\LinkError;
use App\Entity\Rcr;
use App\Entity\Record;
use App\Form\RecordType;
use App\Repository\IlnRepository;
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
     * @Route("/params", name="settings")
     */
    public function settings(Request $request)
    {
        $form = $this->createFormBuilder();
        $session = new Session();
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
            return $this->redirect($this->generateUrl("view_all_ilns"));
        }

        return $this->render("settings.html.twig", ["form" => $form->createView()]);
    }

    /**
     * @Route("/ilns", name="view_all_ilns")
     */
    public function ilns()
    {
        $session = new Session();
        if (!$session->has("winnie")) {
            return $this->redirect($this->generateUrl("settings"));
        }
        $ilns = $this->getDoctrine()->getRepository(Iln::class)->findAll();

        return $this->render("ilns.html.twig", ["ilns" => $ilns]);
    }

    /**
     * @Route("/iln/{code}", name="view_iln")
     */
    public function ilnView(Iln $iln)
    {
        $rcrs = $this->getDoctrine()->getRepository(Rcr::class)->findByIln($iln);

        return $this->render("iln.html.twig",
            [
                "iln" => $iln,
                "rcrs" => $rcrs
            ]
        );
    }

    /**
     * @Route("/iln/{ilnCode}/rcr/{rcrCode}/{ppn?}", name="view_rcr")
     * @Entity("iln", expr="repository.findOneBy({'code': ilnCode})")
     * @Entity("rcr", expr="repository.findOneBy({'code': rcrCode})")
     */
    public function rcrView(Iln $iln, Rcr $rcr, ?string $ppn, EntityManagerInterface $em, Request $request)
    {
        // On va unlocked les notices qui doivent l'être :
        $this->getDoctrine()->getRepository(Record::class)->unlockRecords();
        if (is_null($ppn)) {
            $record = $this->getDoctrine()->getRepository(Record::class)->findOneRandom($rcr);
        } else {
            $record = $this->getDoctrine()->getRepository(Record::class)->findOneBy(["ppn" => $ppn]);
        }


        if (!$record) {
            return $this->render("rcr.html.twig",
                [
                    "iln" => $iln,
                    "rcr" => $rcr,
                    "empty" => 1
                ]
            );
        }
        $record->setLocked();

        $em->persist($record);
        $em->flush();

        $form = $this->createForm(RecordType::class, $record);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $submitButton = $form->getClickedButton();

            $record = $form->getData();

            if ($submitButton->getName() == "validate") {
                // On va mettre à jour
                $record->setStatus(1);
                $countCorrected = $em->getRepository(Record::class)->countCorrectedForRcr($record->getRcrCreate());
                $record->getRcrCreate()->setNumberOfRecordsCorrected($countCorrected);

                $session = new Session();
                $session->start();
                $session->getFlashBag()->add('success', "Correction de la notice enregistrée, elle ne sera plus proposée par cette interface");
            } else {
                $record->setLocked(null);
            }
            $em->persist($record);
            $em->flush();
        }

        return $this->render("rcr.html.twig",
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
     * @Route("/rawrecord/{ppn}", name="raw_record")
     */
    public function rawRecord(string $ppn)
    {
        $xml = file_get_contents("http://www.sudoc.fr/".$ppn.".xml");
        $record = new \SimpleXMLElement($xml);
        $output = "";
        foreach ($record->datafield as $datafield) {
            $tag = (string) $datafield->attributes()["tag"][0];
            $output .= $tag." ";
            foreach ($datafield->subfield as $subfield) {
                $code = (string) $subfield->attributes()["code"][0];
                $value = (string) $subfield;

                $output .= "$$code $value ";
            }
            $output .= "\n";
        }

        return new Response("<pre>$output</pre>");
    }


}
