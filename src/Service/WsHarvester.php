<?php

namespace App\Service;

use App\Entity\BatchImport;
use App\Entity\LinkError;
use App\Entity\PaprikaLink;
use App\Entity\Rcr;
use App\Entity\Record;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use SimpleXMLElement;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\HttpClient;

class WsHarvester
{
    private $em;
    /** @var BatchImport $batchImport */
    private $batchImport;
    private $logger;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    private function storeContent($content): void
    {
        file_put_contents(dirname(__FILE__) . "/../../public/batches/" . $this->batchImport->getId() . ".txt", $content);
    }

    private function processLine(array &$existingRecords, &$ppnPaprikaAlreadySet, $line): void
    {
        $error = preg_split("/\t/", $line);
        if (sizeof($error) > 1) {
            $ppn = trim($error[0]);
            /** @var Record $record */
            if (isset($existingRecords[$ppn])) {
                $record = $existingRecords[$ppn];
            } else {
                $record = $this->em->getRepository(Record::class)->findOneBy(["ppn" => $ppn, "rcrCreate" => $this->batchImport->getRcr()]);
            }

            if (is_null($record)) {
                $this->batchImport->setCountRecords($this->batchImport->getCountRecords() + 1);
                $record = new Record();
                $record->setPpn($ppn);
                $record->setRcrCreate($this->batchImport->getRcr());

                $date = trim($error[4]);
                $date = DateTime::createFromFormat('Y-m-j H:i:s', $date);
                $record->setLastUpdate($date);
                $record->setStatus(Record::RECORD_TODO);
                $record->setDocTypeCode($error[6]);
                $record->setDocTypeLabel($error[7]);
                $record->setBatchImport($this->batchImport);

                $record->setWinnie(0);
                $this->em->persist($record);

                $this->batchImport->addRecord($record);
            } elseif ($record->getBatchImport()->getType() != $this->batchImport->getType()) {
                // On a déjà récupéré cette notice lors d'un autre import, plus besoin de la traiter
                return;
            }

            if ($record->getStatus() == Record::RECORD_FIXED_OUTSIDE) {
                // C'est une notice que l'on est en train de recharger, on va faire le nécessaire
                // Elle redevient une todo
                $record->setStatus(Record::RECORD_TODO);

                // On supprime toutes les erreurs existantes pour avoir quelque chose de frais
                foreach ($record->getLinkErrors() as $linkError) {
                    $this->em->remove($linkError);
                }
            }


            if ($record->getStatus() !== Record::RECORD_TODO) {
                // Si ici on est sur un record qui est en skipped / validated c'est qu'on l'a déjà traité on ne
                // doit pas s'ee occuper à nouveau;
                return;
            }

            $existingRecords[$ppn] = $record;

            $errorObject = new LinkError();
            $errorObject->setErrorText($error[3]);
            $errorObject->setErrorCode($error[5]);
            $paprikaUrl = trim($error[8]);
            if ($paprikaUrl) {
                if (!isset($ppnPaprikaAlreadySet[$ppn])) {
                    $ppnPaprikaAlreadySet[$ppn] = 1;

                    $paprikaUrls = preg_split("/#/", $paprikaUrl);
                    foreach ($paprikaUrls as $tmpUrl) {
                        $paprikaLink = new PaprikaLink();
                        $paprikaLink->setUrl($tmpUrl);
                        $this->em->persist($paprikaLink);
                        $errorObject->addPaprikaLink($paprikaLink);
                    }
                }
            } else {
                $record->setWinnie(1);
                $this->em->persist($record);
            }
            // $errorObject->setRecord($record);
            $record->addLinkError($errorObject);
            $this->em->persist($errorObject);
        }
    }

    private function processContent($content): void
    {

        $lines = preg_split("/\n/", $content);
        $lines = array_slice($lines, 3);

        $ppnPaprikaAlreadySet = [];

        $existingRecords = [];

        if ($this->batchImport->getCountRecords() > 0) {
            // Cas où l'on a déjà importé des notices et que l'on procède à un rafraichissement
            /** @var Record $record */
            foreach ($this->batchImport->getRecords() as $record) {
                $existingRecords[$record->getPpn()] = $record;
            }
        }

        $count = 0;
        foreach ($lines as $line) {
            $this->processLine($existingRecords, $ppnPaprikaAlreadySet, $line);
            $count++;
            if (!($count % 500)) {
                $this->em->flush();
                print "$count / memory : ".memory_get_usage()."\n";
            }
        }
        $this->em->persist($this->batchImport->getRcr());
    }

    private function getUrl()
    {
        if ($this->batchImport->getType() == BatchImport::TYPE_RCR_CREA) {
            $url = sprintf("https://www.idref.fr/AlgoLiens?rcr=%s&paprika=1", $this->batchImport->getRcr()->getCode());
        } elseif ($this->batchImport->getType() == BatchImport::TYPE_UNICA) {
            $url = sprintf("https://www.idref.fr/AlgoLiens?localisationRcr=%s&unica=localisationRcr&paprika=1", $this->batchImport->getRcr()->getCode());
        }
        return $url;
    }

    private function getApiContent($rownum = 10000)
    {
        if ($rownum == 0) {
            // Si on arrive à 0, on abandonne
            return null;
        }
        $url = $this->getUrl();
        $client = HttpClient::create();
        try {
            $currentUrl = $url . "&rownum=" . $rownum;
            print "Appel : ".$currentUrl."\n";
            $this->batchImport->setUrl($currentUrl);
            $response = $client->request('GET', $currentUrl, [
                'max_duration' => 0
            ]);
            $content = $response->getContent();
        } catch (Exception $e) {
            print "Erreur à $rownum, on divise par deux\n";
            return $this->getApiContent(intval($rownum / 2));
        }
        $this->storeContent($content);
        return $content;
    }

    public function runNewBatchAlreadyCreated(BatchImport $batchImport, $logger, $content = null)
    {
        $batchImportId = $batchImport->getId();
        $this->logger = $logger;
        $this->batchImport = $batchImport;
        $this->batchImport->setStartDate(new DateTime());
        $this->batchImport->setStatus(BatchImport::STATUS_RUNNING);
        $this->em->persist($this->batchImport);
        $this->em->flush();

        if (is_null($content)) {
            $content = $this->getApiContent();
        }
        $this->processContent($content);
        $this->batchImport->setEndDate(new DateTime());
        $this->batchImport->setStatus(BatchImport::STATUS_FINISHED);
        $this->em->persist($this->batchImport);
        $this->em->flush();

        $this->em->getRepository(Rcr::class)->updateStatsForRcr($this->batchImport->getRcr());
        $this->em->persist($this->batchImport->getRcr());
        $this->batchImport->updateCountErrors();
        $this->em->persist($this->batchImport);
        $this->em->flush();

        $this->batchImport = $this->em->getRepository(BatchImport::class)->find($batchImportId);
        return $this->batchImport;

    }

    public function runNewBatch(Rcr $rcr, int $batchType, string $content = null)
    {
        $batchImport = new BatchImport($rcr, $batchType);
        $batchImport->setType($batchType);
        $batchImport->setStatus(BatchImport::STATUS_NEW);
        $this->em->persist($batchImport);
        $this->em->flush();
        return $this->runNewBatchAlreadyCreated($batchImport, null, $content);
    }

    public function populateRecordFromAbes(Record $inputRecord)
    {
        $ppn = $inputRecord->getPpn();
        $url = "http://www.sudoc.fr/" . $ppn . ".xml";

        try {
            $client = HttpClient::create();
            $response = $client->request('GET', $url);
            $xml = $response->getContent();
        } catch (ClientException $e) {
            if ($response->getStatusCode() == "404") {
                $inputRecord->setMarcBefore("Notice absente du sudoc public");
                return $inputRecord;
            }
        }
        $abesRecord = new SimpleXMLElement($xml);
        $unimarc = "";
        foreach ($abesRecord->datafield as $datafield) {
            $tag = (string)$datafield->attributes()["tag"][0];
            $unimarc .= $tag . " ";
            foreach ($datafield->subfield as $subfield) {
                $code = (string)$subfield->attributes()["code"][0];
                $value = (string)$subfield;

                if (($tag == 100) && ($code == "a")) {
                    $inputRecord->setYear(substr($value, 9, 4));
                } elseif (($tag == 200) && ($code == "a")) {
                    $inputRecord->setTitle($value);
                } elseif (($tag == 210) && ($code == "d")) {
                    if ($inputRecord->getYear() == "") {
                        $inputRecord->setYear($value);
                    }
                }

                $unimarc .= "$$code $value ";
            }
            $unimarc .= "\n";
        }
        $inputRecord->setMarcBefore($unimarc);

        return $inputRecord;
    }
}
