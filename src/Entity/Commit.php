<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CommitRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommitRepository::class)]
#[ORM\Table(name: 'commits')]
class Commit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'commits')]
    #[ORM\JoinColumn(nullable: false)]
    private GithubRepo $repository;

    #[ORM\Column(length: 40)]
    private string $sha;

    #[ORM\Column(length: 255)]
    private string $authorEmail;

    #[ORM\Column(length: 255)]
    private string $authorName;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column]
    private \DateTimeImmutable $date;

    #[ORM\Column]
    private int $additions = 0;

    #[ORM\Column]
    private int $deletions = 0;

    #[ORM\Column]
    private int $filesChanged = 0;

    #[ORM\Column]
    private bool $isMergeCommit = false;

    #[ORM\Column]
    private array $diffStats = [];

    #[ORM\OneToOne(mappedBy: 'commit', cascade: ['persist', 'remove'])]
    private ?AnalysisResult $analysisResult = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRepository(): GithubRepo
    {
        return $this->repository;
    }

    public function setRepository(GithubRepo $repository): self
    {
        $this->repository = $repository;

        return $this;
    }

    public function getSha(): string
    {
        return $this->sha;
    }

    public function setSha(string $sha): self
    {
        $this->sha = $sha;

        return $this;
    }

    public function getAuthorEmail(): string
    {
        return $this->authorEmail;
    }

    public function setAuthorEmail(string $authorEmail): self
    {
        $this->authorEmail = $authorEmail;

        return $this;
    }

    public function getAuthorName(): string
    {
        return $this->authorName;
    }

    public function setAuthorName(string $authorName): self
    {
        $this->authorName = $authorName;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getAdditions(): int
    {
        return $this->additions;
    }

    public function setAdditions(int $additions): self
    {
        $this->additions = $additions;

        return $this;
    }

    public function getDeletions(): int
    {
        return $this->deletions;
    }

    public function setDeletions(int $deletions): self
    {
        $this->deletions = $deletions;

        return $this;
    }

    public function getFilesChanged(): int
    {
        return $this->filesChanged;
    }

    public function setFilesChanged(int $filesChanged): self
    {
        $this->filesChanged = $filesChanged;

        return $this;
    }

    public function isMergeCommit(): bool
    {
        return $this->isMergeCommit;
    }

    public function setIsMergeCommit(bool $isMergeCommit): self
    {
        $this->isMergeCommit = $isMergeCommit;

        return $this;
    }

    public function getDiffStats(): array
    {
        return $this->diffStats;
    }

    public function setDiffStats(array $diffStats): self
    {
        $this->diffStats = $diffStats;

        return $this;
    }

    public function getAnalysisResult(): ?AnalysisResult
    {
        return $this->analysisResult;
    }

    public function setAnalysisResult(?AnalysisResult $analysisResult): self
    {
        if ($analysisResult !== null) {
            $analysisResult->setCommit($this);
        }

        $this->analysisResult = $analysisResult;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
