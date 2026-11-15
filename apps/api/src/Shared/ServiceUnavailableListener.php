<?php

namespace App\Shared;

use App\Shared\Resource\ProblemResource;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Translates a service of AWS out of reach into a problem+json 503 named by its reason, once Symfony logged its
 * cause (its listener logging the exceptions runs at the priority 0).
 */
#[AsEventListener(priority: self::AFTER_THE_LOG)]
final readonly class ServiceUnavailableListener
{
    private const int AFTER_THE_LOG = -64;

    public function __construct(private NormalizerInterface $normalizer)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $failure = $event->getThrowable();

        if (!$failure instanceof ServiceUnavailable || !str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $problem = new ProblemResource('Service Unavailable', Response::HTTP_SERVICE_UNAVAILABLE, $failure->getMessage(), reason: $failure->reason);
        $event->setResponse(new JsonResponse($this->normalizer->normalize($problem), $problem->status, ['Content-Type' => 'application/problem+json']));
    }
}
