<?php

namespace App\Shared;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Translates a service out of reach, of AWS or outside, into a problem+json 503 (`UnavailabilityProblems`), once Symfony logged its
 * cause (its listener logging the exceptions runs at the priority 0).
 */
#[AsEventListener(priority: self::AFTER_THE_LOG)]
final readonly class ServiceUnavailableListener
{
    private const int AFTER_THE_LOG = -64;

    public function __construct(private UnavailabilityProblems $problems, private NormalizerInterface $normalizer)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $failure = $event->getThrowable();

        if (!$failure instanceof ServiceUnavailable || !str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $problem = $this->problems->of($failure);
        $event->setResponse(new JsonResponse($this->normalizer->normalize($problem), $problem->status, ['Content-Type' => 'application/problem+json']));
    }
}
