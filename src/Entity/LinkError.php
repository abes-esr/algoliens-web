<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
     * @ORM\OneToMany(targetEntity="App\Entity\PaprikaLink", mappedBy="linkError")
     */
    private $paprikaLinks;

    public function __construct()
    {
        $this->paprikaLinks = new ArrayCollection();
    }

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

    /**
     * @return Collection|PaprikaLink[]
     */
    public function getPaprikaLinks(): Collection
    {
        return $this->paprikaLinks;
    }

    public function addPaprikaLink(PaprikaLink $paprikaLink): self
    {
        if (!$this->paprikaLinks->contains($paprikaLink)) {
            $this->paprikaLinks[] = $paprikaLink;
            $paprikaLink->setLinkError($this);
        }

        return $this;
    }

    public function removePaprikaLink(PaprikaLink $paprikaLink): self
    {
        if ($this->paprikaLinks->contains($paprikaLink)) {
            $this->paprikaLinks->removeElement($paprikaLink);
            // set the owning side to null (unless already changed)
            if ($paprikaLink->getLinkError() === $this) {
                $paprikaLink->setLinkError(null);
            }
        }

        return $this;
    }
}
