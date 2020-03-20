<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LinkErrorRepository")
 */
class LinkError
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Iln")
     * @ORM\JoinColumn(nullable=false)
     */
    private $ilnCreate;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Rcr", inversedBy="linkErrors")
     * @ORM\JoinColumn(nullable=false)
     */
    private $rcrCreate;

    /**
     * @ORM\Column(type="string", length=9)
     */
    private $ppn;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Rcr", inversedBy="linkErrors")
     * @ORM\JoinColumn(nullable=false)
     */
    private $rcrUpdate;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $typeDoc;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $textError;

    /**
     * @ORM\Column(type="date")
     */
    private $dateUpdate;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $codeError;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $typeDocLabel;

    /**
     * @ORM\Column(type="smallint", options={"default": 0})
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $paprika;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIlnCreate(): ?Iln
    {
        return $this->ilnCreate;
    }

    public function setIlnCreate(?Iln $ilnCreate): self
    {
        $this->ilnCreate = $ilnCreate;

        return $this;
    }

    public function getRcrCreate(): ?Rcr
    {
        return $this->rcrCreate;
    }

    public function setRcrCreate(?Rcr $rcrCreate): self
    {
        $this->rcrCreate = $rcrCreate;

        return $this;
    }

    public function getPpn(): ?string
    {
        return $this->ppn;
    }

    public function setPpn(string $ppn): self
    {
        $this->ppn = $ppn;

        return $this;
    }

    public function getRcrUpdate(): ?Rcr
    {
        return $this->rcrUpdate;
    }

    public function setRcrUpdate(?Rcr $rcrUpdate): self
    {
        $this->rcrUpdate = $rcrUpdate;

        return $this;
    }

    public function getTypeDoc(): ?string
    {
        return $this->typeDoc;
    }

    public function setTypeDoc(string $typeDoc): self
    {
        $this->typeDoc = $typeDoc;

        return $this;
    }

    public function getTextError(): ?string
    {
        return $this->textError;
    }

    public function setTextError(string $textError): self
    {
        $this->textError = $textError;

        return $this;
    }

    public function getDateUpdate(): ?\DateTimeInterface
    {
        return $this->dateUpdate;
    }

    public function setDateUpdate(\DateTimeInterface $dateUpdate): self
    {
        $this->dateUpdate = $dateUpdate;

        return $this;
    }

    public function getCodeError(): ?string
    {
        return $this->codeError;
    }

    public function setCodeError(string $codeError): self
    {
        $this->codeError = $codeError;

        return $this;
    }

    public function getTypeDocLabel(): ?string
    {
        return $this->typeDocLabel;
    }

    public function setTypeDocLabel(string $typeDocLabel): self
    {
        $this->typeDocLabel = $typeDocLabel;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPaprika(): ?string
    {
        return $this->paprika;
    }

    public function setPaprika(?string $paprika): self
    {
        $this->paprika = $paprika;

        return $this;
    }
}
