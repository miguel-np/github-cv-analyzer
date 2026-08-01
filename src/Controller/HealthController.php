<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Health\HealthChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/health', name: 'app_health')]
    public function index(HealthChecker $checker): JsonResponse
    {
        return new JsonResponse($checker->basic());
    }

    #[Route('/health/detailed', name: 'app_health_detailed')]
    public function detailed(HealthChecker $checker): JsonResponse
    {
        $result = $checker->detailed();
        $status = $result['status'] === 'ok' ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE;

        return new JsonResponse($result, $status);
    }
}
