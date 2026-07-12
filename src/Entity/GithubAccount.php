<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GithubAccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GithubAccountRepository::class)]
#[ORM\Table(name: 'github_accounts')]
class GithubAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 100)]
    private string $githubUsername;

    #[ORM\Column(type: 'text')]
    private string $encryptedToken;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastSyncedAt = null;

    #[ORM\ManyToMany(targetEntity: GithubRepo::class, mappedBy: 'contributors')]
    private Collection $githubRepos;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->githubRepos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getGithubUsername(): string
    {
        return $this->githubUsername;
    }

    public function setGithubUsername(string $githubUsername): self
    {
        $this->githubUsername = $githubUsername;

        return $this;
    }

    public function getEncryptedToken(): string
    {
        return $this->encryptedToken;
    }

    public function setEncryptedToken(string $encryptedToken): self
    {
        $this->encryptedToken = $encryptedToken;

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getGithubRepos(): Collection
    {
        return $this->githubRepos;
    }
}
