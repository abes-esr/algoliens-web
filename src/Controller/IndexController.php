<?php

namespace App\Controller;

use App\Entity\Iln;
use App\Entity\LinkError;
use App\Entity\Rcr;
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
    public function rcrView(Iln $iln, Rcr $rcr)
    {
        $error = $this->getDoctrine()->getRepository(LinkError::class)->findOneRandom($rcr);
        return $this->render("rcr.html.twig",
            [
                "iln" => $iln,
                "rcr" => $rcr,
                "error" => $error

            ]
        );
    }




}
