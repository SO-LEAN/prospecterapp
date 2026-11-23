<?php

declare(strict_types=1);

namespace App\SignIn;

use App\Shared\Resource\ProblemResource;
use App\Shared\Resource\ViolationResource;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Refuses a login email that another member signs in with, like an invalid field: a problem+json 422.
 */
#[AsEventListener]
final readonly class LoginEmailTakenListener
{
    public function __construct(private NormalizerInterface $normalizer)
    {
    }

    /** Named the way a domain refusal would be (ADR 0073). */
    private const string REASON = 'loginEmailTaken';

    public function __invoke(ExceptionEvent $event): void
    {
        if (!$event->getThrowable() instanceof LoginEmailTaken) {
            return;
        }

        $problem = new ProblemResource('Validation Failed', Response::HTTP_UNPROCESSABLE_ENTITY, $event->getThrowable()->getMessage(), [new ViolationResource('email', $event->getThrowable()->getMessage(), self::REASON)], reason: self::REASON);
        $event->setResponse(new JsonResponse($this->normalizer->normalize($problem), $problem->status, ['Content-Type' => 'application/problem+json']));
    }
}
