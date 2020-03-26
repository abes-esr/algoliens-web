<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PaprikaLinkRepository")
 */
class PaprikaLink
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
    private $url;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LinkError", inversedBy="paprikaLinks")
     * @ORM\JoinColumn(nullable=false)
     */
    private $linkError;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getLabel(): string {
        return preg_replace("#https://paprika.idref.fr/\?lastname=(.*)&firstname=(.*)#", "$1, $2", $this->getUrl());
    }

    public function getLinkError(): ?LinkError
    {
        return $this->linkError;
    }

    public function setLinkError(?LinkError $linkError): self
    {
        $this->linkError = $linkError;

        return $this;
    }
}
