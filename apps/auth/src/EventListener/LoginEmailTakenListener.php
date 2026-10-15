<?php

namespace App\EventListener;

use App\LoginEmailTaken;
use App\Resource\ProblemResource;
use App\Resource\ViolationResource;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * A login email another member signs in with is refused like an invalid field: a problem+json 422.
 */
#[AsEventListener]
final readonly class LoginEmailTakenListener
{
    public function __construct(private NormalizerInterface $normalizer)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if (!$event->getThrowable() instanceof LoginEmailTaken) {
            return;
        }

        $problem = new ProblemResource('Validation Failed', Response::HTTP_UNPROCESSABLE_ENTITY, $event->getThrowable()->getMessage(), [new ViolationResource('email', $event->getThrowable()->getMessage())]);
        $event->setResponse(new JsonResponse($this->normalizer->normalize($problem), $problem->status, ['Content-Type' => 'application/problem+json']));
    }
}
