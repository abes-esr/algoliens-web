<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpClient\HttpClient;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BatchImportRepository")
 */
class BatchImport
{
    const TYPE_RCR_CREA = 1;
    const TYPE_UNICA = 2;

    private $startTime = null;
    private $em = null;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $runDate;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Rcr", inversedBy="batchImports")
     * @ORM\JoinColumn(nullable=false)
     */
    private $rcr;

    /**
     * @ORM\Column(type="smallint")
     */
    private $type;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Record", mappedBy="batchImport")
     */
    private $records;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private $countRecords;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private $countErrors;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $duration;

    public function __construct(Rcr $rcr = null, int $type = null, EntityManagerInterface $em = null)
    {
        $this->records = new ArrayCollection();
        $this->setRcr($rcr);
        $this->setType($type);
        $this->setRunDate(new \DateTime());
        $this->setCountRecords(0);
        $this->setCountErrors(0);
        $this->em = $em;
        $this->em->persist($this);
        $this->em->flush();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRunDate(): ?\DateTimeInterface
    {
        return $this->runDate;
    }

    public function setRunDate(\DateTimeInterface $runDate): self
    {
        $this->runDate = $runDate;

        return $this;
    }

    public function getRcr(): ?Rcr
    {
        return $this->rcr;
    }

    public function setRcr(?Rcr $rcr): self
    {
        $this->rcr = $rcr;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function getTypeLabel(): string
    {
        switch ($this->getType()) {
            case self::TYPE_RCR_CREA:
                return "RCR CRÉATEUR";
            case self::TYPE_UNICA:
                return "UNICA";
        }
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection|Record[]
     */
    public function getRecords(): Collection
    {
        return $this->records;
    }

    public function addRecord(Record $record): self
    {
        if (!$this->records->contains($record)) {
            $this->records[] = $record;
            $record->setBatchImport($this);
        }

        return $this;
    }

    public function removeRecord(Record $record): self
    {
        if ($this->records->contains($record)) {
            $this->records->removeElement($record);
            // set the owning side to null (unless already changed)
            if ($record->getBatchImport() === $this) {
                $record->setBatchImport(null);
            }
        }

        return $this;
    }

    private function processUrl() {
        $content = $this->getAlgoLienContent($url, 10000);
        if (is_null($content)) {
            return null;
        }
        $this->io->writeln("WS : fin (".(microtime(true) - $start).")");

        $this->processContent($rcr, $urlCallType, $content);
        $this->io->writeln(sprintf("<info>Durée de traitement </info> : %s",  (microtime(true) - $startRcr)));
    }


    private function getUrl() {
        if ($this->getType() == self::TYPE_RCR_CREA) {
            $url = sprintf("https://www.idref.fr/AlgoLiens?rcr=%s&paprika=1", $this->getRcr()->getCode());
        } elseif ($this->getType() == self::TYPE_UNICA)
        {
            $url = sprintf("https://www.idref.fr/AlgoLiens?localisationRcr=%s&unica=localisationRcr&paprika=1", $this->getRcr()->getCode());
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

    private function storeContent($content) {
        file_put_contents(dirname(__FILE__)."/../../var/batches/".$this->getId().".txt", $content);
    }

    private function processLine(array &$existingRecords, &$ppnPaprikaAlreadySet, $line) {
        $error = preg_split("/\t/", $line);
        if (sizeof($error) > 1) {
            $ppn = trim($error[0]);
            if (isset($existingRecords[$ppn])) {
                $record = $existingRecords[$ppn];
            } else {
                $record = $this->em->getRepository(Record::class)->findOneBy(["ppn" => $ppn, "rcrCreate" => $this->getRcr()]);
            }

            if (is_null($record)) {
                $this->setCountRecords($this->getCountRecords() + 1);
                $record = new Record();
                $record->setPpn($ppn);
                $record->setRcrCreate($this->getRcr());

                $date = trim($error[4]);
                $date = \DateTime::createFromFormat('Y-m-j H:i:s', $date );
                $record->setLastUpdate($date);
                $record->setStatus(0);
                $record->setDocTypeCode($error[6]);
                $record->setDocTypeLabel($error[7]);
                $record->setBatchImport($this);

                $record->setWinnie(0);
                $this->em->persist($record);
            } elseif ($record->getBatchImport()->getType() != $this->getType() ) {
                // On a déjà récupéré cette notice lors d'un autre import, plus besoin de la traiter
                return;
            }

            $existingRecords[$ppn] = $record;

            $this->setCountErrors($this->getCountErrors() + 1);
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
        $this->em->persist($this->getRcr());
    }

    public function run() {
        $startTime = microtime(true);
        $content = $this->getApiContent();
        $this->processContent($content);
        $this->setDuration(microtime(true) - $startTime);

        $this->em->flush();
    }

    public function getCountRecords(): ?int
    {
        return $this->countRecords;
    }

    public function setCountRecords(int $countRecords): self
    {
        $this->countRecords = $countRecords;

        return $this;
    }

    public function getCountErrors(): ?int
    {
        return $this->countErrors;
    }

    public function setCountErrors(int $countErrors): self
    {
        $this->countErrors = $countErrors;

        return $this;
    }

    public function getDuration(): ?float
    {
        return $this->duration;
    }

    public function setDuration(?float $duration): self
    {
        $this->duration = $duration;

        return $this;
    }
}
