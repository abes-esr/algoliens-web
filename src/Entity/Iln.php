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

    public function __construct()
    {
        $this->rcrs = new ArrayCollection();
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
    public function getRcrs(): Collection
    {
        $output = $this->rcrs->filter(function($rcr) { return $rcr->getActive() == 1; });
        return $output;
    }

    /**
     * @return Collection|Rcr[]
     */
    public function getRcrsWithRecords(): Collection
    {
        $output = $this->rcrs->filter(function($rcr) { return (($rcr->getActive() == 1) and ($rcr->getNumberOfRecords() > 0)); });
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

    public function getNumberOfRecords() {
        return array_reduce($this->getRcrs()->getValues(), function ($total, $rcr) { $total += $rcr->getNumberOfRecords(); return $total; });
    }

    public function getNumberOfRecordsCorrected() {
        return array_reduce($this->getRcrs()->getValues(), function ($total, $rcr) { $total += $rcr->getNumberOfRecordsCorrected(); return $total; });
    }

    public function getNumberOfRecordsHandled() {
        return array_reduce($this->getRcrs()->getValues(), function ($total, $rcr) { $total += $rcr->getNumberOfRecordsHandled(); return $total; });
    }

    public function getNumberOfRecordsReprise() {
        return array_reduce($this->getRcrs()->getValues(), function ($total, $rcr) { $total += $rcr->getNumberOfRecordsReprise(); return $total; });
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

}
