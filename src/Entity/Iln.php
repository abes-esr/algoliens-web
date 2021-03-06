<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\IlnRepository")
 */
class Iln
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=12)
     */
    private $code;

    /**
     * @ORM\Column(type="integer")
     */
    private $number;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $label;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Rcr", mappedBy="iln")
     */
    private $rcrs;

    /**
     * @ORM\Column(type="string", length=4)
     */
    private $secret;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\SkipReason", mappedBy="Iln")
     */
    private $skipReasons;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $hasLanguages;

    public function __construct()
    {
        $this->rcrs = new ArrayCollection();
        $this->skipReasons = new ArrayCollection();
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

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;

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

    /**
     * @return Collection|Rcr[]
     */
    public function getRcrsWithRecords(): Collection
    {
        $output = $this->rcrs->filter(function ($rcr) {
            return (($rcr->getActive() == 1) and ($rcr->getNumberOfRecords() > 0));
        });
        return $output;
    }

    /**
     * @return Collection|Rcr[]
     */
    public function getRcrsWithRecordsFixedOutside(): Collection
    {
        $output = $this->rcrs->filter(function (Rcr $rcr) {
            return (($rcr->getActive() == 1) and ($rcr->getNumberOfRecordsFixedOutside() > 0));
        });
        return $output;
    }

    public function addRcr(Rcr $rcr): self
    {
        if (!$this->rcrs->contains($rcr)) {
            $this->rcrs[] = $rcr;
            $rcr->setIln($this);
        }

        return $this;
    }

    public function removeRcr(Rcr $rcr): self
    {
        if ($this->rcrs->contains($rcr)) {
            $this->rcrs->removeElement($rcr);
            // set the owning side to null (unless already changed)
            if ($rcr->getIln() === $this) {
                $rcr->setIln(null);
            }
        }

        return $this;
    }

    public function getNumberOfRecords()
    {
        return array_reduce($this->getRcrs()->getValues(), function ($total, $rcr) {
            $total += $rcr->getNumberOfRecords();
            return $total;
        });
    }

    /**
     * @return Collection|Rcr[]
     */
    public function getRcrs(): Collection
    {
        $output = $this->rcrs->filter(function ($rcr) {
            return $rcr->getActive() == 1;
        });
        return $output;
    }

    public function getNumberOfRecordsCorrected()
    {
        return array_reduce($this->getRcrs()->getValues(), function ($total, Rcr $rcr) {
            $total += $rcr->getCountRecordsByStatus(record::RECORD_VALIDATED);
            return $total;
        });
    }

    public function getNumberOfRecordsHandled()
    {
        return array_reduce($this->getRcrs()->getValues(), function ($total, Rcr $rcr) {
            $total += $rcr->getCountRecordsByStatus(Record::RECORD_SKIPPED) + $rcr->getCountRecordsByStatus(Record::RECORD_VALIDATED);
            return $total;
        });
    }

    public function getNumberOfRecordsReprise()
    {
        return array_reduce($this->getRcrs()->getValues(), function ($total, $rcr) {
            $total += $rcr->getNumberOfRecordsReprise();
            return $total;
        });
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): self
    {
        $this->secret = $secret;

        return $this;
    }

    public function getDefaultSkipReason()
    {
        return $this->skipReasons[0];
    }

    /**
     * @return Collection|SkipReason[]
     */
    public function getSkipReasons(): Collection
    {
        return $this->skipReasons;
    }

    public function addSkipReason(SkipReason $skipReason): self
    {
        if (!$this->skipReasons->contains($skipReason)) {
            $this->skipReasons[] = $skipReason;
            $skipReason->setIln($this);
        }

        return $this;
    }

    public function removeSkipReason(SkipReason $skipReason): self
    {
        if ($this->skipReasons->contains($skipReason)) {
            $this->skipReasons->removeElement($skipReason);
            // set the owning side to null (unless already changed)
            if ($skipReason->getIln() === $this) {
                $skipReason->setIln(null);
            }
        }

        return $this;
    }

    public function getHasLanguages(): ?bool
    {
        return $this->hasLanguages;
    }

    public function setHasLanguages(bool $hasLanguages): self
    {
        $this->hasLanguages = $hasLanguages;

        return $this;
    }

}
