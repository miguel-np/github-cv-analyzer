<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PullRequestRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PullRequestRepository::class)]
#[ORM\Table(name: 'pull_requests')]
class PullRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'pullRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private GithubRepo $repository;

    #[ORM\Column]
    private int $githubId;

    #[ORM\Column(length: 500)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $body = null;

    #[ORM\Column(length: 20)]
    private string $state;

    #[ORM\Column]
    private bool $merged = false;

    #[ORM\Column]
    private int $additions = 0;

    #[ORM\Column]
    private int $deletions = 0;

    #[ORM\Column]
    private int $changedFiles = 0;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $mergedAt = null;

    #[ORM\Column]
    private array $metadata = [];

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
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

    public function getGithubId(): int
    {
        return $this->githubId;
    }

    public function setGithubId(int $githubId): self
    {
        $this->githubId = $githubId;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function isMerged(): bool
    {
        return $this->merged;
    }

    public function setMerged(bool $merged): self
    {
        $this->merged = $merged;

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

    public function getChangedFiles(): int
    {
        return $this->changedFiles;
    }

    public function setChangedFiles(int $changedFiles): self
    {
        $this->changedFiles = $changedFiles;

        return $this;
    }

    public function getMergedAt(): ?DateTimeImmutable
    {
        return $this->mergedAt;
    }

    public function setMergedAt(?DateTimeImmutable $mergedAt): self
    {
        $this->mergedAt = $mergedAt;

        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
