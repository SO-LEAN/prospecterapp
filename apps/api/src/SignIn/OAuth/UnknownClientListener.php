<?php

namespace App\SignIn\OAuth;

use App\Shared\Resource\ProblemResource;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * An AI the facade cannot trust to be sent back where it asks is answered a problem+json 400, never redirected to an
 * address not checked (RFC 6749, 4.1.2.1; ADR 0157).
 */
#[AsEventListener]
final readonly class UnknownClientListener
{
    private const string REASON = 'unknownClient';

    public function __construct(private NormalizerInterface $normalizer)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if (!$event->getThrowable() instanceof UnknownClient) {
            return;
        }

        $problem = new ProblemResource('Bad Request', Response::HTTP_BAD_REQUEST, $event->getThrowable()->getMessage(), reason: self::REASON);
        $event->setResponse(new JsonResponse($this->normalizer->normalize($problem), $problem->status, ['Content-Type' => 'application/problem+json']));
    }
}
