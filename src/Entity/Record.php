<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RecordRepository")
 * @ORM\Table(indexes={@Index(name="idx_ppn", columns={"ppn"})})
 * @ORM\HasLifecycleCallbacks()
 */
class Record
{
    const RECORD_TODO = 0;
    const RECORD_VALIDATED = 1;
    const RECORD_SKIPPED = 2;
    const RECORD_FIXED_OUTSIDE = 3;

    /**
     * @ORM\Column(type="datetime", nullable=true)
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
     * @ORM\OneToMany(targetEntity="App\Entity\LinkError", mappedBy="record", cascade={"remove"})
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

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\BatchImport", inversedBy="records")
     */
    private $batchImport;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\SkipReason", inversedBy="records")
     */
    private $skipReason;

    /**
     * @ORM\Column(type="string", length=6, nullable=true)
     */
    private $lang;

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

    public function getLastUpdate(): ?DateTimeInterface
    {
        return $this->lastUpdate;
    }

    public function setLastUpdate(DateTimeInterface $lastUpdate): self
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }

    public function getLocked(): ?DateTimeInterface
    {
        return $this->locked;
    }

    public function setLocked(DateTimeInterface $lastUpdate = null): self
    {
        $endOfLock = new DateTime();
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

    public function getBatchImport(): ?BatchImport
    {
        return $this->batchImport;
    }

    public function setBatchImport(?BatchImport $batchImport): self
    {
        $this->batchImport = $batchImport;

        return $this;
    }


    /**
     * @ORM\PreUpdate()
     */
    public function setUpdatedAt(): void {
        $this->updatedAt = new DateTime();
    }

    public function getSkipReason(): ?SkipReason
    {
        return $this->skipReason;
    }

    public function setSkipReason(?SkipReason $skipReason): self
    {
        $this->skipReason = $skipReason;

        return $this;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }

    public function setLang(?string $lang): self
    {
        $this->lang = $lang;

        return $this;
    }

    public function guessLang()
    {
        if ($this->getMarcBefore() == "Notice absente du sudoc public") {
            return "zzz";
        }

        $lines = preg_split("/\n/", $this->getMarcBefore());
        foreach ($lines as $line) {
            if (preg_match("/^101/", $line)) {
                $lang = preg_replace('/^.*\\$a ([^ ]*) .*$/', "$1", $line);
                if (strlen($lang) == 3) {
                    return $lang;
                }
            }
        }

        # Si l'on n'a pas trouvé de 101 on renvoie zzz
        return "zzz";
    }
}
