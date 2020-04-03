<?php

namespace App\Controller;

use App\Entity\Iln;
use App\Entity\Rcr;
use App\Repository\BatchImportRepository;
use App\Repository\IlnRepository;
use App\Repository\RcrRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin")
 */

class AdminController extends AbstractController
{
    /**
     * @Route("/", name="admin_index")
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
}
