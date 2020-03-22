<?php

namespace App\Controller;

use App\Entity\Iln;
use App\Entity\LinkError;
use App\Entity\Rcr;
use App\Entity\Record;
use App\Form\RecordType;
use Doctrine\ORM\EntityManagerInterface;
use function PHPSTORM_META\type;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index()
    {
        $ilns = $this->getDoctrine()->getRepository(Iln::class)->findAll();
        return $this->render("index.html.twig", ["ilns" => $ilns]);
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
     * @Route("/iln/{ilnCode}/rcr/{rcrCode}", name="view_rcr")
     * @Entity("iln", expr="repository.findOneBy({'code': ilnCode})")
     * @Entity("rcr", expr="repository.findOneBy({'code': rcrCode})")
     */
    public function rcrView(Iln $iln, Rcr $rcr, EntityManagerInterface $em, Request $request)
    {
        $session = new Session();

        // On va unlocked les notices qui doivent l'être :
        $this->getDoctrine()->getRepository(Record::class)->unlockRecords();
        $record = $this->getDoctrine()->getRepository(Record::class)->findOneRandom($rcr);
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

                $session->getFlashBag()->add('success', "+ 1 notice corrigée ! ( $countCorrected )");
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




}
