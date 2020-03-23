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
     * @ORM\Column(type="string", length=255)
     */
    private $errorText;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $errorCode;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Record", inversedBy="linkErrors")
     * @ORM\JoinColumn(nullable=false)
     */
    private $record;

    /**
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    private $paprika;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getErrorText(): ?string
    {
        return $this->errorText;
    }

    public function setErrorText(string $errorText): self
    {
        $this->errorText = $errorText;

        return $this;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function setErrorCode(string $errorCode): self
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    public function getRecord(): ?Record
    {
        return $this->record;
    }

    public function setRecord(?Record $record): self
    {
        $this->record = $record;

        return $this;
    }

    public function getPaprika(): ?string
    {
        return trim($this->paprika);
    }

    public function setPaprika(?string $paprika): self
    {
        $this->paprika = $paprika;

        return $this;
    }
}
