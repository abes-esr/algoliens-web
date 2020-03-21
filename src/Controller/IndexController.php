<?php

namespace App\Controller;

use App\Entity\Iln;
use App\Entity\LinkError;
use App\Entity\Rcr;
use App\Entity\Record;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function rcrView(Iln $iln, Rcr $rcr, EntityManagerInterface $em)
    {
        $record = $this->getDoctrine()->getRepository(Record::class)->findOneRandom($rcr);
        $record->setLocked();

        $em->persist($record);
        $em->flush();

        return $this->render("rcr.html.twig",
            [
                "iln" => $iln,
                "rcr" => $rcr,
                "record" => $record
            ]
        );
    }




}
