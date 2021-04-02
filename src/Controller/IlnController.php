<?php


namespace App\Controller;


use App\Entity\BatchImport;
use App\Entity\Iln;
use App\Entity\Record;
use App\Repository\IlnRepository;
use App\Repository\RecordRepository;
use App\Service\AbesLanguages;
use Box\Spout\Common\Entity\Style\Style;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Common\Creator\WriterFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
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
     * @Route("/chantier/{code}-{secret}/stats/export/{format}", name="export_iln_stats")
     */
    public function ilnExportStats(Iln $iln, string $format)
    {
        if ($format == "xlsx") {
            $type = Type::XLSX;
            $contentType = "application/vnd.ms-excel";
        } elseif ($format == "ods") {
            $type = Type::ODS;
            $contentType = "application/vnd.oasis.opendocument.spreadsheet";
        } elseif ($format == "csv") {
            $type = Type::CSV;
            $contentType = "text/csv";
        } else {
            return new Response("Format d'export " . $format . " inconnu");
        }

        $styleBold = (new StyleBuilder())->setFontBold()->build();

        $outputFilename = "stats-iln" . $iln->getNumber() . "-" . date("Ymd") . "." . $format;

        $tmpFile = sys_get_temp_dir() . '/' . uniqid(time() * rand(1, 999)) . '.xlsx';
        $writer = WriterFactory::createFromType($type);
        $writer->openToFile($tmpFile);

        $header = [
            WriterEntityFactory::createCell('Annéee'),
            WriterEntityFactory::createCell('Mois'),
            WriterEntityFactory::createCell('Jour'),
            WriterEntityFactory::createCell('Corrigés - Notices'),
            WriterEntityFactory::createCell('Corrigés - Liens'),
            WriterEntityFactory::createCell('À reprendre - Notices'),
            WriterEntityFactory::createCell('À reprendre - Liens'),
        ];

        $writer->addRow(WriterEntityFactory::createRow($header, $styleBold));
        $stats = $this->getDoctrine()->getRepository(Iln::class)->getStats($iln);

        $total = [
            "nbrecordsvalidated" => 0,
            "nberrorsvalidated" => 0,
            "nbrecordsskipped" => 0,
            "nberrorsskipped" => 0,
        ];

        foreach ($stats as $year => $subtable) {
            foreach ($subtable as $month => $subsubtable) {
                foreach ($subsubtable as $day => $values) {
                    $row = [
                        WriterEntityFactory::createCell($year),
                        WriterEntityFactory::createCell($month),
                        WriterEntityFactory::createCell($day),
                        WriterEntityFactory::createCell(intval($values[Record::RECORD_VALIDATED]["nbrecords"])),
                        WriterEntityFactory::createCell(intval($values[Record::RECORD_VALIDATED]["nberrors"])),
                        WriterEntityFactory::createCell(intval($values[Record::RECORD_SKIPPED]["nbrecords"])),
                        WriterEntityFactory::createCell(intval($values[Record::RECORD_SKIPPED]["nberrors"])),
                    ];
                    $writer->addRow(WriterEntityFactory::createRow($row));

                    $total["nbrecordsvalidated"] += intval($values[Record::RECORD_VALIDATED]["nbrecords"]);
                    $total["nberrorsvalidated"] += intval($values[Record::RECORD_VALIDATED]["nberrors"]);
                    $total["nbrecordsskipped"] += intval($values[Record::RECORD_SKIPPED]["nbrecords"]);
                    $total["nberrorsskipped"] += intval($values[Record::RECORD_SKIPPED]["nberrors"]);
                }
            }
        }

        $rowTotal = [
            WriterEntityFactory::createCell(null),
            WriterEntityFactory::createCell(null),
            WriterEntityFactory::createCell("Total"),
            WriterEntityFactory::createCell($total["nbrecordsvalidated"]),
            WriterEntityFactory::createCell($total["nberrorsvalidated"]),
            WriterEntityFactory::createCell($total["nbrecordsskipped"]),
            WriterEntityFactory::createCell($total["nberrorsskipped"]),
        ];
        $writer->addRow(WriterEntityFactory::createRow($rowTotal, $styleBold));
        $writer->close();

        return new Response(file_get_contents($tmpFile), 200,
            array(
                'Content-Type' => $contentType,
                'Content-Disposition' => 'attachment; filename="'.$outputFilename.'"',
            ));
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
    public function ilnVienwReprises(Iln $iln)
    {
        $skipReasons = $iln->getSkipReasons();
        return $this->render("iln/reprises.html.twig", ["iln" => $iln, "skipReaons" => $skipReasons]);
    }

    /**
     * @Route("/chantier/{code}-{secret}/cherche-ppn", name="search_ppn")
     */
    public function searchPpn(Iln $iln, Request $request, RecordRepository $recordRepository)
    {
        $form = $this->createFormBuilder(null)
            ->add("ppn", TextType::class)
            ->add("submit", SubmitType::class, [
                "label" => "Rechercher"
            ])
            ->setAction($this->generateUrl("search_ppn", ["code" => $iln->getCode(), "secret" => $iln->getSecret()]))
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $ppn = $data["ppn"];

            $record = $recordRepository->findByPpnAndIln($ppn, $iln);
            if (!is_null($record)) {
                return $this->redirect(
                    $this->generateUrl(
                        "view_record_permalink",
                        [
                            "ilnCode" => $record->getRcrCreate()->getIln()->getCode(),
                            "ilnSecret" => $record->getRcrCreate()->getIln()->getSecret(),
                            "rcrCode" => $record->getRcrCreate()->getCode(),
                            "ppn" => $record->getPpn(),
                            "idRecord" => $record->getId()
                        ]
                    )
                );
            } else {
                $session = $request->getSession();
                $session->getFlashBag()->add('danger', "Le PPN <strong>$ppn</strong> n'a pas été trouvé pour ce chantier.");
                return $this->redirect(
                    $this->generateUrl("view_iln", ["code" => $iln->getCode(), "secret" => $iln->getSecret()])
                );
            }
        }
        return $this->render("iln/search_ppn.html.twig", [
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/chantier/{code}-{secret}/export-batch/{id}", name="export_batch_import")
     */
    public function exportBatchImport(BatchImport $batchImport)
    {
        $filename = "export_batch_" . $batchImport->getId() . ".txt";
        $content = "";
        foreach ($batchImport->getRecords() as $record) {
            $content .= $record->getPpn() . "\n";
        }

        $response = new Response($content);

        // Create the disposition of the file
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );

        // Set the content disposition
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'text/plain');
        // Dispatch request
        return $response;
    }
}