<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RecordRepository")
 * @ORM\Table(indexes={@Index(name="idx_ppn", columns={"ppn"})})
 */
class Record
{
    const SKIP_PHYSICAL_NEEDED = 2;
    const SKIP_OTHER_REASON = 3;

    /**
     * @ORM\Column(type="datetime", columnDefinition="DATETIME on update CURRENT_TIMESTAMP")
     */
    private $updatedAt;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=9)
     */
    private $ppn;

    /**
     * @ORM\Column(type="smallint", options={"default": 0})
     */
    private $status;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\LinkError", mappedBy="record")
     * @ORM\OrderBy({"errorCode" = "ASC"})
     */
    private $linkErrors;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Rcr", inversedBy="records")
     * @ORM\JoinColumn(nullable=false)
     */
    private $rcrCreate;

    /**
     * @ORM\Column(type="date")
     */
    private $lastUpdate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $locked;

    /**
     * @ORM\Column(type="string", length=5)
     */
    private $docTypeCode;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $docTypeLabel;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $marcBefore;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $marcAfter;

    /**
     * @ORM\Column(type="smallint")
     */
    private $urlCallType;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $winnie;

    /**
     * @ORM\Column(type="string", length=512, nullable=true)
     */
    private $comment;

    /**
     * @ORM\Column(type="string", length=512, nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=12, nullable=true)
     */
    private $year;

    public function __construct()
    {
        $this->linkErrors = new ArrayCollection();
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

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
            $linkError->setRecord($this);
        }

        return $this;
    }

    public function removeLinkError(LinkError $linkError): self
    {
        if ($this->linkErrors->contains($linkError)) {
            $this->linkErrors->removeElement($linkError);
            // set the owning side to null (unless already changed)
            if ($linkError->getRecord() === $this) {
                $linkError->setRecord(null);
            }
        }

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

    public function getLastUpdate(): ?\DateTimeInterface
    {
        return $this->lastUpdate;
    }

    public function setLastUpdate(\DateTimeInterface $lastUpdate): self
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }

    public function getLocked(): ?\DateTimeInterface
    {
        return $this->locked;
    }

    public function setLocked(\DateTimeInterface $lastUpdate = null): self
    {
        $endOfLock = new \DateTime();
        $endOfLock = $endOfLock->modify("+1 hour");
        $this->locked = $endOfLock;

        return $this;
    }

    public function getDocTypeCode(): ?string
    {
        return $this->docTypeCode;
    }

    public function setDocTypeCode(string $docTypeCode): self
    {
        $this->docTypeCode = $docTypeCode;

        return $this;
    }

    public function getDocTypeLabel(): ?string
    {
        return $this->docTypeLabel;
    }

    public function setDocTypeLabel(string $docTypeLabel): self
    {
        $this->docTypeLabel = $docTypeLabel;

        return $this;
    }

    public function getMarcBefore(): ?string
    {
        return $this->marcBefore;
    }

    public function setMarcBefore(?string $marcBefore): self
    {
        $this->marcBefore = $marcBefore;

        return $this;
    }

    public function getMarcAfter(): ?string
    {
        return $this->marcAfter;
    }

    public function setMarcAfter(?string $marcAfter): self
    {
        $this->marcAfter = $marcAfter;

        return $this;
    }

    public function getUrlCallType(): ?int
    {
        return $this->urlCallType;
    }

    public function setUrlCallType(int $urlCallType): self
    {
        $this->urlCallType = $urlCallType;

        return $this;
    }

    public function getWinnie(): ?bool
    {
        return $this->winnie;
    }

    public function setWinnie(?bool $winnie): self
    {
        $this->winnie = $winnie;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getYear(): ?string
    {
        return $this->year;
    }

    public function setYear(?string $year): self
    {
        $this->year = $year;

        return $this;
    }

}
