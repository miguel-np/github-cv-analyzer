<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AnalysisResultRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnalysisResultRepository::class)]
#[ORM\Table(name: 'analysis_results')]
class AnalysisResult
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'analysisResult')]
    #[ORM\JoinColumn(nullable: false)]
    private Commit $commit;

    #[ORM\Column(length: 50)]
    private string $provider;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $model = null;

    #[ORM\Column]
    private array $classification = [];

    #[ORM\Column(nullable: true)]
    private ?int $tokensUsed = null;

    #[ORM\Column(nullable: true)]
    private ?float $cost = null;

    #[ORM\Column(nullable: true)]
    private ?int $durationMs = null;

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

    public function getCommit(): Commit
    {
        return $this->commit;
    }

    public function setCommit(Commit $commit): self
    {
        $this->commit = $commit;

        return $this;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function getClassification(): array
    {
        return $this->classification;
    }

    public function setClassification(array $classification): self
    {
        $this->classification = $classification;

        return $this;
    }

    public function getTokensUsed(): ?int
    {
        return $this->tokensUsed;
    }

    public function setTokensUsed(?int $tokensUsed): self
    {
        $this->tokensUsed = $tokensUsed;

        return $this;
    }

    public function getCost(): ?float
    {
        return $this->cost;
    }

    public function setCost(?float $cost): self
    {
        $this->cost = $cost;

        return $this;
    }

    public function getDurationMs(): ?int
    {
        return $this->durationMs;
    }

    public function setDurationMs(?int $durationMs): self
    {
        $this->durationMs = $durationMs;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
