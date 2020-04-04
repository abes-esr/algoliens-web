<?php
    namespace App\Service;

    use App\Entity\BatchImport;
    use App\Entity\LinkError;
    use App\Entity\PaprikaLink;
    use App\Entity\Rcr;
    use App\Entity\Record;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Component\HttpClient\HttpClient;

    class WsHarvester {
        private $em;
        private $batchImport;

        public function __construct(EntityManagerInterface $em)
        {
            $this->em = $em;
        }
        private function storeContent($content) {
            file_put_contents(dirname(__FILE__)."/../../public/batches/".$this->batchImport->getId().".txt", $content);
        }

        private function processLine(array &$existingRecords, &$ppnPaprikaAlreadySet, $line) {
            $error = preg_split("/\t/", $line);
            if (sizeof($error) > 1) {
                $ppn = trim($error[0]);
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
                    $date = \DateTime::createFromFormat('Y-m-j H:i:s', $date );
                    $record->setLastUpdate($date);
                    $record->setStatus(0);
                    $record->setDocTypeCode($error[6]);
                    $record->setDocTypeLabel($error[7]);
                    $record->setBatchImport($this->batchImport);

                    $record->setWinnie(0);
                    $this->em->persist($record);
                } elseif ($record->getBatchImport()->getType() != $this->batchImport->getType() ) {
                    // On a déjà récupéré cette notice lors d'un autre import, plus besoin de la traiter
                    return;
                }

                $existingRecords[$ppn] = $record;

                $this->batchImport->setCountErrors($this->batchImport->getCountErrors() + 1);
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

        private function processContent($content) {
            $lines = preg_split("/\n/", $content);
            $lines = array_slice($lines, 3);

            $ppnPaprikaAlreadySet = [];

            $existingRecords = [];
            foreach ($lines as $line) {
                $this->processLine($existingRecords, $ppnPaprikaAlreadySet, $line);
            }
            $this->em->persist($this->batchImport->getRcr());
        }

        private function getUrl() {
            if ($this->batchImport->getType() == BatchImport::TYPE_RCR_CREA) {
                $url = sprintf("https://www.idref.fr/AlgoLiens?rcr=%s&paprika=1", $this->batchImport->getRcr()->getCode());
            } elseif ($this->batchImport->getType() == BatchImport::TYPE_UNICA)
            {
                $url = sprintf("https://www.idref.fr/AlgoLiens?localisationRcr=%s&unica=localisationRcr&paprika=1", $this->batchImport->getRcr()->getCode());
            }
            return $url;
        }

        private function getApiContent($rownum = 10000) {
            if ($rownum == 0) {
                // Si on arrive à 0, on abandonne
                $this->io->writeln("<error>Récupération impossible, on abandonne</error>");
                return null;
            }
            $url = $this->getUrl()."&rownum=".$rownum;
            $client = HttpClient::create();
            try {
                $response = $client->request('GET', $url."&rownum=".$rownum, [
                    'max_duration' => 0
                ]);
                $content = $response->getContent();
            } catch (\Exception $e) {
                return $this->getApiContent(intval($rownum / 2));
            }
            $this->storeContent($content);
            return $content;
        }

        public function runNewBatch(Rcr $rcr, int $batchType) {
            $startTime = microtime(true);

            $this->batchImport = new BatchImport($rcr, $batchType);
            $this->batchImport->setRunDate(new \DateTime());
            $this->batchImport->setStatus(BatchImport::STATUS_RUNNING);
            $this->em->persist($this->batchImport);
            $this->em->flush();

            $content = $this->getApiContent();
            $this->processContent($content);
            $this->batchImport->setDuration(microtime(true) - $startTime);
            $this->batchImport->setStatus(BatchImport::STATUS_FINISHED);
            $this->em->persist($this->batchImport);
            $this->em->flush();
            return $this->batchImport;
        }
    }
