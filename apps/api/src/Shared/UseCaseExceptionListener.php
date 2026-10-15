<?php

namespace App\Shared;

use App\Shared\Resource\ProblemResource;
use App\Shared\Resource\ViolationResource;
use Prospecter\Core\SharedKernel\Domain\BusinessRuleViolation;
use Prospecter\Core\SharedKernel\Domain\NotAllowed;
use Prospecter\Core\SharedKernel\Domain\NotFound;
use Prospecter\Core\SharedKernel\Domain\NotPossibleInItsState;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Translates the refusals of the core into problem+json responses: unknown (or not mine) is a 404, an
 * intention its state does not offer (no link) a 409, one the asker has no right to a 403, any other business
 * rule a 422.
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

        if (!$this->isARefusalOfTheCore($exception) || !str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $problem = $this->problem($exception);
        $event->setResponse(new JsonResponse($this->normalizer->normalize($problem), $problem->status, ['Content-Type' => 'application/problem+json']));
    }

    /**
     * @phpstan-assert-if-true NotFound|BusinessRuleViolation $exception
     */
    private function isARefusalOfTheCore(\Throwable $exception): bool
    {
        return $exception instanceof NotFound || $exception instanceof BusinessRuleViolation;
    }

    private function problem(NotFound|BusinessRuleViolation $exception): ProblemResource
    {
        return match (true) {
            $exception instanceof NotFound => new ProblemResource('Not Found', Response::HTTP_NOT_FOUND, $exception->getMessage()),
            $exception instanceof NotAllowed => new ProblemResource('Forbidden', Response::HTTP_FORBIDDEN, $exception->getMessage(), [$this->violation($exception)]),
            $exception instanceof NotPossibleInItsState => new ProblemResource('Conflict', Response::HTTP_CONFLICT, $exception->getMessage(), [$this->violation($exception)]),
            $exception instanceof BusinessRuleViolation => new ProblemResource('Validation Failed', Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage(), [$this->violation($exception)]),
        };
    }

    private function violation(BusinessRuleViolation $exception): ViolationResource
    {
        return new ViolationResource('*' === $exception->field ? '' : $exception->field, $exception->getMessage());
    }
}
