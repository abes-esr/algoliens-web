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
     * @ORM\Column(type="smallint")
     */
    private $harvested;

    /**
     * @ORM\Column(type="boolean", options={"default": 1})
     */
    private $active;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\BatchImport", mappedBy="rcr")
     */
    private $batchImports;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private $recordsStatus0;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private $recordsStatus1;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private $recordsStatus2;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private $recordsStatus3;

    public function __construct()
    {
        $this->records = new ArrayCollection();
        $this->batchImports = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getLabel();
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
        return $this->getRecordsStatus0() + $this->getRecordsStatus1() + $this->getRecordsStatus2() + $this->getRecordsStatus3();
    }

    public function getNumberOfRecordsCorrected(): ?int
    {
        return $this->getCountRecordsByStatus(Record::RECORD_VALIDATED);
    }


    public function getRecordsStatus0(): ?int
    {
        return $this->recordsStatus0;
    }

    public function setRecordsStatus0(int $recordsStatus0): self
    {
        $this->recordsStatus0 = $recordsStatus0;

        return $this;
    }

    public function getRecordsStatus1(): ?int
    {
        return $this->recordsStatus1;
    }

    public function setRecordsStatus1(int $recordsStatus1): self
    {
        $this->recordsStatus1 = $recordsStatus1;

        return $this;
    }

    public function getRecordsStatus2(): ?int
    {
        return $this->recordsStatus2;
    }

    public function setRecordsStatus2(int $recordsStatus2): self
    {
        $this->recordsStatus2 = $recordsStatus2;

        return $this;
    }

    public function getRecordsStatus3(): ?int
    {
        return $this->recordsStatus3;
    }

    public function setRecordsStatus3(int $recordsStatus3): self
    {
        $this->recordsStatus3 = $recordsStatus3;

        return $this;
    }

    public function getNumberOfRecordsAvailable(): int
    {
        return $this->getCountRecordsByStatus(Record::RECORD_TODO);
    }

    public function getCountRecordsByStatus(int $status)
    {
        switch ($status) {
            case Record::RECORD_TODO:
                return $this->getRecordsStatus0();
            case Record::RECORD_VALIDATED:
                return $this->getRecordsStatus1();
            case Record::RECORD_SKIPPED:
                return $this->getRecordsStatus2();
            case Record::RECORD_FIXED_OUTSIDE:
                return $this->getRecordsStatus3();
        }
        return 0;
    }

    public function getNumberOfRecordsReprise(): int
    {
        return $this->getCountRecordsByStatus(Record::RECORD_SKIPPED);
    }

    public function getNumberOfRecordsFixedOutside(): int
    {
        return $this->getCountRecordsByStatus(Record::RECORD_FIXED_OUTSIDE);
    }

    public function setNumberOfRecordsCorrected(int $value): void
    {
        $this->setCountRecordsByStatus(Record::RECORD_VALIDATED, $value);
    }

    public function setNumberOfRecordsReprise(int $value): void
    {
        $this->setCountRecordsByStatus(Record::RECORD_SKIPPED, $value);
    }

    public function setCountRecordsByStatus(int $status, int $value): void
    {
        switch ($status) {
            case Record::RECORD_TODO:
                $this->setRecordsStatus0($value);
                return;
            case Record::RECORD_VALIDATED:
                $this->setRecordsStatus1($value);
                return;
            case Record::RECORD_SKIPPED:
                $this->setRecordsStatus2($value);
                return;
            case Record::RECORD_FIXED_OUTSIDE:
                $this->setRecordsStatus3($value);
                return;
        }
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

    public function addBatchImport(BatchImport $batchImport): self
    {
        if (!$this->batchImports->contains($batchImport)) {
            $this->batchImports[] = $batchImport;
            $batchImport->setRcr($this);
        }

        return $this;
    }

    public function hasBatchRun(int $batchType)
    {
        foreach ($this->getBatchImports() as $batchImport) {
            if ($batchImport->getType() == $batchType) {
                return $batchImport;
            }
        }
        return false;
    }

    /**
     * @return Collection|BatchImport[]
     */
    public function getBatchImports(): Collection
    {
        return $this->batchImports;
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
}
