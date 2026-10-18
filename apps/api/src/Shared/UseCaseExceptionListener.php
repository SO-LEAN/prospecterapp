<?php

namespace App\Shared;

use App\Shared\Resource\ProblemResource;
use App\Shared\Resource\ViolationResource;
use Prospecter\Core\SharedKernel\Application\Exception\ErrorType;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Translates the refusals of the use cases into problem+json responses, by their type: unknown (or not mine)
 * is a 404, an intention its state does not offer (no link) a 409, one the asker has no right to a 403, any
 * other business rule a 422, each named by its reason for the client to word it (ADR 0073). Any other
 * exception is a failure, left to Symfony.
 */
#[AsEventListener]
final readonly class UseCaseExceptionListener
{
    public function __construct(private NormalizerInterface $normalizer)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $refusal = $event->getThrowable();

        if (!$refusal instanceof UseCaseRefused || !str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $problem = $this->problem($refusal);
        $event->setResponse(new JsonResponse($this->normalizer->normalize($problem), $problem->status, ['Content-Type' => 'application/problem+json']));
    }

    private function problem(UseCaseRefused $refusal): ProblemResource
    {
        [$status, $title] = match ($refusal->type) {
            ErrorType::NotFound => [Response::HTTP_NOT_FOUND, 'Not Found'],
            ErrorType::NotAllowed => [Response::HTTP_FORBIDDEN, 'Forbidden'],
            ErrorType::NotPossibleInItsState => [Response::HTTP_CONFLICT, 'Conflict'],
            ErrorType::BusinessRuleViolated => [Response::HTTP_UNPROCESSABLE_ENTITY, 'Validation Failed'],
        };

        return new ProblemResource($title, $status, $refusal->getMessage(), $this->violationsOf($refusal), reason: $refusal->reason);
    }

    /**
     * @return list<ViolationResource>
     */
    private function violationsOf(UseCaseRefused $refusal): array
    {
        return ErrorType::NotFound === $refusal->type ? [] : [new ViolationResource($refusal->field, $refusal->getMessage(), $refusal->reason, $refusal->context)];
    }
}
