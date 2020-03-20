<?php

namespace App\Entity;

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
     * @ORM\OneToMany(targetEntity="App\Entity\LinkError", mappedBy="rcrCreate")
     */
    private $linkErrors;

    public function __construct()
    {
        $this->linkErrors = new ArrayCollection();
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

    public function getUpdated(): ?\DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(\DateTimeInterface $updated): self
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
     * @return Collection|LinkError[]
     */
    public function getLinkErrors(): Collection
    {
        return $this->linkErrors;
    }

    public function addLinkError(LinkError $linkError): self
    {
        if (!$this->linkErrors->contains($linkError)) {
            $this->linkErrors[] = $linkError;
            $linkError->setRcrCreate($this);
        }

        return $this;
    }

    public function removeLinkError(LinkError $linkError): self
    {
        if ($this->linkErrors->contains($linkError)) {
            $this->linkErrors->removeElement($linkError);
            // set the owning side to null (unless already changed)
            if ($linkError->getRcrCreate() === $this) {
                $linkError->setRcrCreate(null);
            }
        }

        return $this;
    }
}
