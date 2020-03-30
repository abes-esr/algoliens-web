<?php

namespace App\Controller;

use App\Entity\Record;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AjaxController extends AbstractController
{
    /**
     * @Route("/ajax/rawrecord/{ppn}", name="raw_record")
     */
    public function rawRecord(EntityManagerInterface $em, string $ppn)
    {
        $url = "http://www.sudoc.fr/".$ppn.".xml";
        $xml = file_get_contents($url);
        $record = new \SimpleXMLElement($xml);
        $unimarc = "";
        $title = "";
        foreach ($record->datafield as $datafield) {
            $tag = (string) $datafield->attributes()["tag"][0];
            $unimarc .= $tag." ";
            foreach ($datafield->subfield as $subfield) {
                $code = (string) $subfield->attributes()["code"][0];
                $value = (string) $subfield;

                if ( ($tag == 200) && ($code == "a") ) {
                    $title = $value;
                }
                $unimarc .= "$$code $value ";
            }
            $unimarc .= "\n";
        }


        $records = $this->getDoctrine()->getRepository(Record::class)->findBy(["ppn" => $ppn]);
        foreach ($records as $record) {
            $record->setMarcBefore($unimarc);
            $em->persist($record);
        }
        $em->flush();

        return new JsonResponse([
            "title" => $title,
            "unimarc_record" => "<pre>$unimarc</pre>"
        ]);
    }

    /**
     * @Route("/ajax/veriflocal/{ppn}/{rcr}", name="verif_loca")
     */
    public function check(string $ppn, string $rcr) {
        $json = file_get_contents(sprintf("https://www.sudoc.fr/services/multiwhere/%s&format=text/json", $ppn));
        $holdings = json_decode($json);
        $li = "";
        foreach ($holdings->sudoc->query->result as $result) {
            if ($result->rcr == $rcr) {
                return new Response("");
            }
            $li .= "<li>".$result->rcr." - ".$result->shortname."</li>";
        }

        $response = "<div class='alert alert-danger'><p><strong>Attention</strong> : ce RCR n'est plus localisé sur cette notice. Vous pouvez tout de même corriger les liens aux autorités mais redoublez de vigilance !</p>";
        if ($li != '') {

            $response .= "<p>Sont désormais localisés sur cette notice : <ul>$li</ul></p>";
        }
        $response .= "</div>";
        return new Response($response);
    }
}