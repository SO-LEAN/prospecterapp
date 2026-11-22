<?php

declare(strict_types=1);

namespace App\Shared;

use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Translates the refusals of the use cases into problem+json responses (`RefusalProblems`). Any other exception is a
 * failure, left to Symfony.
 */
#[AsEventListener]
final readonly class UseCaseExceptionListener
{
    public function __construct(private RefusalProblems $problems, private NormalizerInterface $normalizer)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $refusal = $event->getThrowable();

        if (!$refusal instanceof UseCaseRefused || !str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $problem = $this->problems->of($refusal);
        $event->setResponse(new JsonResponse($this->normalizer->normalize($problem), $problem->status, ['Content-Type' => 'application/problem+json']));
    }
}
