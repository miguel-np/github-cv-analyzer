<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GithubRepoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GithubRepoRepository::class)]
#[ORM\Table(name: 'repositories')]
class GithubRepo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $githubId;

    #[ORM\Column(length: 255)]
    private string $fullName;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $language = null;

    #[ORM\Column]
    private int $stars = 0;

    #[ORM\Column]
    private int $forks = 0;

    #[ORM\Column]
    private bool $isFork = false;

    #[ORM\Column]
    private bool $isPrivate = false;

    #[ORM\Column]
    private array $metadata = [];

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastSyncedAt = null;

    #[ORM\ManyToMany(targetEntity: GithubAccount::class, inversedBy: 'githubRepos')]
    private Collection $contributors;

    #[ORM\OneToMany(targetEntity: Commit::class, mappedBy: 'repository', cascade: ['remove'])]
    private Collection $commits;

    #[ORM\OneToMany(targetEntity: PullRequest::class, mappedBy: 'repository', cascade: ['remove'])]
    private Collection $pullRequests;

    #[ORM\OneToMany(targetEntity: Issue::class, mappedBy: 'repository', cascade: ['remove'])]
    private Collection $issues;

    #[ORM\ManyToMany(targetEntity: Technology::class, inversedBy: 'repositories')]
    private Collection $technologies;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->contributors = new ArrayCollection();
        $this->commits = new ArrayCollection();
        $this->pullRequests = new ArrayCollection();
        $this->issues = new ArrayCollection();
        $this->technologies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getStars(): int
    {
        return $this->stars;
    }

    public function setStars(int $stars): self
    {
        $this->stars = $stars;

        return $this;
    }

    public function getForks(): int
    {
        return $this->forks;
    }

    public function setForks(int $forks): self
    {
        $this->forks = $forks;

        return $this;
    }

    public function isFork(): bool
    {
        return $this->isFork;
    }

    public function setIsFork(bool $isFork): self
    {
        $this->isFork = $isFork;

        return $this;
    }

    public function isPrivate(): bool
    {
        return $this->isPrivate;
    }

    public function setIsPrivate(bool $isPrivate): self
    {
        $this->isPrivate = $isPrivate;

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

    public function getLastSyncedAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncedAt;
    }

    public function setLastSyncedAt(?\DateTimeImmutable $lastSyncedAt): self
    {
        $this->lastSyncedAt = $lastSyncedAt;

        return $this;
    }

    public function getContributors(): Collection
    {
        return $this->contributors;
    }

    public function addContributor(GithubAccount $account): self
    {
        if (!$this->contributors->contains($account)) {
            $this->contributors->add($account);
        }

        return $this;
    }

    public function getCommits(): Collection
    {
        return $this->commits;
    }

    public function getPullRequests(): Collection
    {
        return $this->pullRequests;
    }

    public function getIssues(): Collection
    {
        return $this->issues;
    }

    public function getTechnologies(): Collection
    {
        return $this->technologies;
    }

    public function addTechnology(Technology $technology): self
    {
        if (!$this->technologies->contains($technology)) {
            $this->technologies->add($technology);
        }

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
