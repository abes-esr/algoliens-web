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

    const STATUS_RUNNING = 1;
    const STATUS_FINISHED = 2;
    const STATUS_ERROR = 3;
    const STATUS_CANCEL = 4;

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

    /**
     * @ORM\Column(type="smallint")
     */
    private $status;

    public function __construct(Rcr $rcr = null, int $type = null, EntityManagerInterface $em = null)
    {
        $this->records = new ArrayCollection();
        $this->setRcr($rcr);
        $this->setType($type);
        $this->setRunDate(new \DateTime());
        $this->setCountRecords(0);
        $this->setCountErrors(0);
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

    public function getIlnCode(): string
    {
        return $this->getRcr()->getIln()->getCode();
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function getStatusLabel(): string
    {
        switch ($this->status) {
            case BatchImport::STATUS_CANCEL:
                return "Annulé";
            case BatchImport::STATUS_FINISHED:
                return "Terminé";
            case BatchImport::STATUS_ERROR:
                return "Erreur";
            case BatchImport::STATUS_RUNNING:
                return "En cours";
        }
        return $this->status;
    }

    public function getStatusClass(): string
    {
        switch ($this->status) {
            case BatchImport::STATUS_CANCEL:
                return "warning";
            case BatchImport::STATUS_FINISHED:
                return "success";
            case BatchImport::STATUS_ERROR:
                return "warning";
            case BatchImport::STATUS_RUNNING:
                return "warning";
        }
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }
}