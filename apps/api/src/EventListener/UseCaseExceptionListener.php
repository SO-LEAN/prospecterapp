<?php

namespace App\EventListener;

use App\Api\Resource\ProblemResource;
use App\Api\Resource\ViolationResource;
use Prospecter\Core\Exception\UseCase\NotFoundException;
use Prospecter\Core\Exception\UseCase\UnauthorizedException;
use Prospecter\Core\Exception\UseCase\UseCaseException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Translates the business exceptions thrown by the use cases into problem+json responses.
 */
#[AsEventListener]
final readonly class UseCaseExceptionListener
{
    public function __construct(private NormalizerInterface $normalizer)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof UseCaseException || !str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $problem = match (true) {
            $exception instanceof NotFoundException => new ProblemResource('Not Found', Response::HTTP_NOT_FOUND, $exception->getMessage()),
            $exception instanceof UnauthorizedException => new ProblemResource('Forbidden', Response::HTTP_FORBIDDEN, 'You are not allowed to perform this action.'),
            default => new ProblemResource('Validation Failed', Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage(), $this->violations($exception)),
        };

        $event->setResponse(new JsonResponse($this->normalizer->normalize($problem), $problem->status, ['Content-Type' => 'application/problem+json']));
    }

    /**
     * @return list<ViolationResource>
     */
    private function violations(UseCaseException $exception): array
    {
        $violations = [];

        foreach ($exception->getRequestErrors() as $field => $message) {
            $violations[] = new ViolationResource('*' === $field ? '' : (string) $field, (string) $message);
        }

        return $violations;
    }
}
