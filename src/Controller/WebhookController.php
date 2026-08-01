<?php

declare(strict_types=1);

namespace App\Controller;

use App\Message\ProcessWebhookMessage;
use App\Service\GitHub\WebhookVerifier;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class WebhookController extends AbstractController
{
    #[Route('/webhook/github', name: 'app_webhook_github', methods: ['POST'])]
    public function github(
        Request $request,
        WebhookVerifier $verifier,
        MessageBusInterface $bus,
    ): JsonResponse {
        $payload = $request->getContent();
        $headers = $request->headers->all();

        $flatHeaders = [];
        foreach ($headers as $key => $values) {
            $flatHeaders[strtolower($key)] = $values[0] ?? '';
        }

        if (!$verifier->verify($payload, $flatHeaders)) {
            return new JsonResponse(['status' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if (!isset($flatHeaders['x-github-event'])) {
            return new JsonResponse(['status' => 'missing_event_header'], Response::HTTP_BAD_REQUEST);
        }

        $event = $flatHeaders['x-github-event'];

        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new JsonResponse(['status' => 'invalid_payload'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_array($data)) {
            return new JsonResponse(['status' => 'invalid_payload'], Response::HTTP_BAD_REQUEST);
        }

        if ($event === 'ping') {
            return new JsonResponse(['status' => 'ok']);
        }

        $bus->dispatch(new ProcessWebhookMessage($event, $data));

        return new JsonResponse(['status' => 'accepted']);
    }
}
