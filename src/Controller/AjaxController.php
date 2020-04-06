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
        $year = "";

        foreach ($record->datafield as $datafield) {
            $tag = (string) $datafield->attributes()["tag"][0];
            $unimarc .= $tag." ";
            foreach ($datafield->subfield as $subfield) {
                $code = (string) $subfield->attributes()["code"][0];
                $value = (string) $subfield;

                if ( ($tag == 100) && ($code == "a") ) {
                    $year = substr($value, 9,4);
                } elseif ( ($tag == 200) && ($code == "a") ) {
                    $title = $value;
                }
                elseif ( ($tag == 210) && ($code == "d") ) {
                    if ($year == '') {
                        $year = $value;
                    }
                }

                $unimarc .= "$$code $value ";
            }
            $unimarc .= "\n";
        }


        $records = $this->getDoctrine()->getRepository(Record::class)->findBy(["ppn" => $ppn]);
        foreach ($records as $record) {
            $record->setMarcBefore($unimarc);
            $record->setTitle($title);
            $record->setYear($year);
            $em->persist($record);
        }
        $em->flush();

        return new JsonResponse([
            "ppn" => $ppn,
            "year" => $year,
            "title" => $title,
            "unimarc_record" => "<pre>$unimarc</pre>"
        ]);
    }

    /**
     * @Route("/ajax/veriflocal/{ppn}/{rcr}", name="verif_loca")
     */
    public function check(string $ppn, string $rcr) {
        $url = sprintf("https://www.sudoc.fr/services/multiwhere/%s&format=text/json", $ppn);
        $json = file_get_contents($url);
        $holdings = json_decode($json);

        $li = "";
        if (is_array($holdings->sudoc->query->result->library)) {
            foreach ($holdings->sudoc->query->result->library as $result) {
                if ($result->rcr == $rcr) {
                    return new Response("");
                }
            }
            $li .= "<li>".$result->rcr." - ".$result->shortname."</li>";
        } else {
            $result = $holdings->sudoc->query->result->library;
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
