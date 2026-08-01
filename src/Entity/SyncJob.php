<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SyncJobRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SyncJobRepository::class)]
#[ORM\Table(name: 'sync_jobs')]
class SyncJob
{
    public const TYPE_FULL = 'full';
    public const TYPE_INCREMENTAL = 'incremental';

    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private GithubAccount $githubAccount;

    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_FULL;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column]
    private int $itemsProcessed = 0;

    #[ORM\Column]
    private array $errorLog = [];

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $finishedAt = null;

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

    public function getGithubAccount(): GithubAccount
    {
        return $this->githubAccount;
    }

    public function setGithubAccount(GithubAccount $githubAccount): self
    {
        $this->githubAccount = $githubAccount;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getItemsProcessed(): int
    {
        return $this->itemsProcessed;
    }

    public function setItemsProcessed(int $itemsProcessed): self
    {
        $this->itemsProcessed = $itemsProcessed;

        return $this;
    }

    public function getErrorLog(): array
    {
        return $this->errorLog;
    }

    public function setErrorLog(array $errorLog): self
    {
        $this->errorLog = $errorLog;

        return $this;
    }

    public function getStartedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?DateTimeImmutable $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getFinishedAt(): ?DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?DateTimeImmutable $finishedAt): self
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
