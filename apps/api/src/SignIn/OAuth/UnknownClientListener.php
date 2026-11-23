<?php

declare(strict_types=1);

namespace App\SignIn\OAuth;

use App\Shared\Resource\ProblemResource;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * When the facade cannot trust the address an AI asks to be sent back to, it answers a problem+json 400. It never
 * redirects to an unchecked address (RFC 6749, 4.1.2.1; ADR 0157).
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
