<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
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
        if (($session->has("winnie")) && ($session->get("winnie") == "1")) {
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
}
