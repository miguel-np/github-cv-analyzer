<?php

declare(strict_types=1);

namespace App\Service\Health;

use App\Service\GitHub\GitHubClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

final readonly class HealthChecker
{
    public function __construct(
        private EntityManagerInterface $em,
        private ?GitHubClientInterface $githubClient,
        private ?TransportInterface $messengerTransport,
    ) {
    }

    /**
     * @return array{status: string, version: string, timestamp: string}
     */
    public function basic(): array
    {
        return [
            'status' => 'ok',
            'version' => 'v0.6.0',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array{status: string, checks: array<string, array{status: string, latency_ms?: int, error?: string}>}
     */
    public function detailed(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'messenger' => $this->checkMessenger(),
        ];

        if ($this->githubClient !== null && $this->githubClient->isAuthenticated()) {
            $checks['github_api'] = $this->checkGitHubApi();
        }

        $allOk = true;

        foreach ($checks as $check) {
            if ($check['status'] !== 'ok') {
                $allOk = false;
                break;
            }
        }

        return [
            'status' => $allOk ? 'ok' : 'degraded',
            'checks' => $checks,
        ];
    }

    /**
     * @return array{status: string, latency_ms: int}
     */
    private function checkDatabase(): array
    {
        $start = microtime(true);

        try {
            $conn = $this->em->getConnection();
            $conn->executeQuery('SELECT 1');
            $latency = (int) ((microtime(true) - $start) * 1000);

            return ['status' => 'ok', 'latency_ms' => $latency];
        } catch (\Throwable $e) {
            $latency = (int) ((microtime(true) - $start) * 1000);

            return ['status' => 'error', 'latency_ms' => $latency, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{status: string, pending_messages?: int, error?: string}
     */
    private function checkMessenger(): array
    {
        if ($this->messengerTransport === null) {
            return ['status' => 'ok', 'message' => 'transport not available'];
        }

        try {
            $pending = $this->messengerTransport->get();

            return ['status' => 'ok', 'pending_messages' => count($pending)];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{status: string, error?: string}
     */
    private function checkGitHubApi(): array
    {
        try {
            $this->githubClient?->getCurrentUsername();

            return ['status' => 'ok'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }
}
