<?php

namespace App\EventListener;

use App\Api\Resource\ProblemResource;
use App\Api\Resource\ViolationResource;
use Prospecter\Core\Application\Exception\NotFoundException;
use Prospecter\Core\Application\Exception\UnauthorizedException;
use Prospecter\Core\Application\Exception\UseCaseException;
use Prospecter\Core\Domain\Shared\BusinessRuleViolation;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Translates the refusals of the core (use cases and business rules) into problem+json responses.
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
     * @phpstan-assert-if-true UseCaseException|BusinessRuleViolation $exception
     */
    private function isARefusalOfTheCore(\Throwable $exception): bool
    {
        return $exception instanceof UseCaseException || $exception instanceof BusinessRuleViolation;
    }

    private function problem(UseCaseException|BusinessRuleViolation $exception): ProblemResource
    {
        return match (true) {
            $exception instanceof NotFoundException => new ProblemResource('Not Found', Response::HTTP_NOT_FOUND, $exception->getMessage()),
            $exception instanceof UnauthorizedException => new ProblemResource('Forbidden', Response::HTTP_FORBIDDEN, 'You are not allowed to perform this action.'),
            $exception instanceof BusinessRuleViolation => new ProblemResource('Validation Failed', Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage(), [$this->violation($exception->field, $exception->getMessage())]),
            $exception instanceof UseCaseException => new ProblemResource('Validation Failed', Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage(), $this->violations($exception)),
        };
    }

    /**
     * @return list<ViolationResource>
     */
    private function violations(UseCaseException $exception): array
    {
        return array_map($this->violation(...), array_keys($exception->getRequestErrors()), array_values($exception->getRequestErrors()));
    }

    private function violation(string $field, string $message): ViolationResource
    {
        return new ViolationResource('*' === $field ? '' : $field, $message);
    }
}
