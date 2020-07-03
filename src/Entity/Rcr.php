<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RcrRepository")
 */
class Rcr
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=9)
     */
    private $code;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $label;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updated;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Iln", inversedBy="rcrs")
     * @ORM\JoinColumn(nullable=false)
     */
    private $iln;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Record", mappedBy="rcrCreate")
     */
    private $records;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"default": 0})
     */
    private $numberOfRecords;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"default": 0})
     */
    private $numberOfRecordsCorrected;

    /**
     * @ORM\Column(type="smallint")
     */
    private $harvested;

    /**
     * @ORM\Column(type="boolean", options={"default": 1})
     */
    private $active;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"default": 0})
     */
    private $numberOfRecordsReprise;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\BatchImport", mappedBy="rcr")
     */
    private $batchImports;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"default": 0})
     */
    private $numberOfRecordsFixedOutside;

    public function __construct()
    {
        $this->records = new ArrayCollection();
        $this->batchImports = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getLabel();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getUpdated(): ?DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(DateTimeInterface $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    public function getIln(): ?Iln
    {
        return $this->iln;
    }

    public function setIln(?Iln $iln): self
    {
        $this->iln = $iln;

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
            $record->setRcrCreate($this);
        }

        return $this;
    }

    public function removeRecord(Record $record): self
    {
        if ($this->records->contains($record)) {
            $this->records->removeElement($record);
            // set the owning side to null (unless already changed)
            if ($record->getRcrCreate() === $this) {
                $record->setRcrCreate(null);
            }
        }

        return $this;
    }

    public function getNumberOfRecords(): ?int
    {
        if (is_null($this->numberOfRecords)) {
            return 0;
        }
        return $this->numberOfRecords;
    }

    public function setNumberOfRecords(?int $numberOfRecords): self
    {
        $this->numberOfRecords = $numberOfRecords;

        return $this;
    }

    public function getNumberOfRecordsCorrected(): ?int
    {
        if (is_null($this->numberOfRecordsCorrected)) {
            return 0;
        }
        return $this->numberOfRecordsCorrected;
    }

    public function setNumberOfRecordsCorrected(?int $numberOfRecordsCorrected): self
    {
        $this->numberOfRecordsCorrected = $numberOfRecordsCorrected;

        return $this;
    }

    public function getNumberOfRecordsAvailable(): ?int
    {
        return ($this->getNumberOfRecords() - $this->getNumberOfRecordsCorrected() - $this->getNumberOfRecordsReprise());
    }

    public function getNumberOfRecordsHandled(): ?int
    {
        return ($this->getNumberOfRecordsCorrected() + $this->getNumberOfRecordsReprise());
    }


    public function getHarvested(): ?int
    {
        return $this->harvested;
    }

    public function setHarvested(int $harvested): self
    {
        $this->harvested = $harvested;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getNumberOfRecordsReprise(): ?int
    {
        if (is_null($this->numberOfRecordsReprise)) {
            return 0;
        }
        return $this->numberOfRecordsReprise;
    }

    public function setNumberOfRecordsReprise(?int $numberOfRecordsReprise): self
    {
        $this->numberOfRecordsReprise = $numberOfRecordsReprise;

        return $this;
    }

    /**
     * @return Collection|BatchImport[]
     */
    public function getBatchImports(): Collection
    {
        return $this->batchImports;
    }

    public function addBatchImport(BatchImport $batchImport): self
    {
        if (!$this->batchImports->contains($batchImport)) {
            $this->batchImports[] = $batchImport;
            $batchImport->setRcr($this);
        }

        return $this;
    }

    public function hasBatchRun(int $batchType) {
        foreach ($this->getBatchImports() as $batchImport) {
            if ($batchImport->getType() == $batchType) {
                return $batchImport;
            }
        }
        return false;
    }

    public function removeBatchImport(BatchImport $batchImport): self
    {
        if ($this->batchImports->contains($batchImport)) {
            $this->batchImports->removeElement($batchImport);
            // set the owning side to null (unless already changed)
            if ($batchImport->getRcr() === $this) {
                $batchImport->setRcr(null);
            }
        }

        return $this;
    }

    public function getNumberOfRecordsFixedOutside(): ?int
    {
        return $this->numberOfRecordsFixedOutside;
    }

    public function setNumberOfRecordsFixedOutside(int $numberOfRecordsFixedOutside): self
    {
        $this->numberOfRecordsFixedOutside = $numberOfRecordsFixedOutside;

        return $this;
    }
}
