<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SkipReasonRepository")
 */
class SkipReason
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Iln", inversedBy="skipReasons")
     * @ORM\JoinColumn(nullable=false)
     */
    private $Iln;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Record", mappedBy="skipReason")
     */
    private $records;

    public function __construct()
    {
        $this->records = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getIln(): ?Iln
    {
        return $this->Iln;
    }

    public function setIln(?Iln $Iln): self
    {
        $this->Iln = $Iln;

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
            $record->setSkipReason($this);
        }

        return $this;
    }

    public function removeRecord(Record $record): self
    {
        if ($this->records->contains($record)) {
            $this->records->removeElement($record);
            // set the owning side to null (unless already changed)
            if ($record->getSkipReason() === $this) {
                $record->setSkipReason(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getDescription();
    }
}
