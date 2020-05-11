<?php


namespace App\Controller;


use App\Entity\Iln;
use App\Entity\Rcr;
use App\Entity\Record;
use App\Repository\RecordRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Routing\Annotation\Route;

class RcrController extends AbstractController
{
    /**
     * @Route("/chantier/{ilnCode}-{ilnSecret}/rcr/{rcrCode}/unlock", name="force_unlock")
     * @Entity("iln", expr="repository.findOneBy({'code': ilnCode})")
     * @Entity("rcr", expr="repository.findOneBy({'code': rcrCode})")
     */
    public function forceUnlock(Rcr $rcr, Iln $iln, RecordRepository $recordRepository, Request $request)
    {
        $unlockedRecords = $recordRepository->forceUnlockRecordsForRcr($rcr);
        $session = $request->getSession();
        $session->getFlashBag()->add('success', $unlockedRecords . " notices libérées.");
        return $this->redirect($this->generateUrl('view_record_rcr', ['ilnCode' => $iln->getCode(), 'rcrCode' => $rcr->getCode(), "ilnSecret" => $iln->getSecret()]));
    }


    /**
     * @Route("//chantier/{ilnCode}-{ilnSecret}/rcr/{rcrCode}/reprise", name="view_rcr_reprise")
     * @Entity("iln", expr="repository.findOneBy({'code': ilnCode})")
     * @Entity("rcr", expr="repository.findOneBy({'code': rcrCode})")
     */
    public function rcrViewReprise(Iln $iln, Rcr $rcr)
    {
        $records = $this->getDoctrine()->getRepository(Record::class)->findRepriseNeeded($rcr);
        $recordsByReason = array();
        $recordsByReason[0] = ["skipReason" => null, "records" => []];
        foreach ($iln->getSkipReasons() as $skipReason) {
            $recordsByReason[$skipReason->getId()] = ["skipReason" => $skipReason, "records" => []];
        }

        foreach ($records as $record) {
            if (is_null($record->getSkipReason())) {
                $recordsByReason[0]["records"][] = $record;
            } else {
                $recordsByReason[$record->getSkipReason()->getId()]["records"][] = $record;
            }
        }
        return $this->render("rcr/view_reprise.html.twig", [
            "iln" => $iln,
            "rcr" => $rcr,
            "recordsByReason" => $recordsByReason,
            "countRecords" => sizeof($records)

        ]);
    }

    /**
     * @Route("/chantier/{ilnCode}-{ilnSecret}/rcr/{rcrCode}/export/{format}", name="export_rcr_reprise")
     * @Entity("iln", expr="repository.findOneBy({'code': ilnCode})")
     * @Entity("rcr", expr="repository.findOneBy({'code': rcrCode})")
     */
    public function rcrReprise(Iln $iln, Rcr $rcr, string $format)
    {
        if (!in_array($format, ["xls", "xlsx", "ods"])) {
            throw $this->createNotFoundException("Format d'export '$format' non prévu");
        }

        if ($rcr->getIln() !== $iln) {
            throw $this->createNotFoundException("Problème de concordance entre l'ILN et le RCR");
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setTitle("Notices à reprendre pour le RCR ".$rcr->getCode())
            ->setCreator("Algoliens web");

        $sheet = $spreadsheet->getActiveSheet();
        $records = $this->getDoctrine()->getRepository(Record::class)->findRepriseNeeded($rcr);
        $count = 2;
        $sheet->setCellValue("A1", "PPN");
        $sheet->setCellValue("B1", "Titre");
        $sheet->setCellValue("C1", "Année");
        $sheet->setCellValue("D1", "Note");

        $headerSkipReason = false;
        /** @var Record $record */
        foreach ($records as $record) {
            $sheet->setCellValue('A'.$count, $record->getPpn());
            $sheet->setCellValue('B'.$count, $record->getTitle());
            $sheet->setCellValue('C'.$count, $record->getYear());
            $sheet->setCellValue('D'.$count, $record->getComment());
            if (!is_null($record->getSkipReason())) {
                $sheet->setCellValue('E'.$count, $record->getSkipReason()->getDescription());
                $headerSkipReason = true;
            }

            $count++;
        }

        if ($headerSkipReason) {
            $sheet->setCellValue("E1", "Type de reprise");
        }

        $mimeTypes = new MimeTypes();
        $mimeType = $mimeTypes->getMimeTypes($format)[0];
        $filename = "export_".$rcr->getCode().".".$format;

        if ($format == "ods") {
            $writer = new Ods($spreadsheet);
        } elseif ($format === "xls") {
            $writer = new Xls($spreadsheet);
        } elseif ($format === "xlsx") {
            $writer = new Xlsx($spreadsheet);
        } else {
            throw $this->createNotFoundException("Erreur de format");
        }

        $response =  new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );

        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$filename.'"');
        $response->headers->set('Cache-Control','max-age=0');
        return $response;

    }
}