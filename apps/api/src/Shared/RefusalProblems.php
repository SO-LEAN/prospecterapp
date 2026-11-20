<?php

namespace App\Shared;

use App\Shared\Resource\ProblemResource;
use App\Shared\Resource\ViolationResource;
use Prospecter\Core\SharedKernel\Application\Exception\ErrorType;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Symfony\Component\HttpFoundation\Response;

/**
 * The problem a refusal of a use case is, by its type: unknown (or not mine) is a 404, an intention its state does
 * not offer (no link) a 409, one the asker has no right to a 403, any other business rule a 422, each named by its
 * reason for the client to word it (ADR 0073).
 */
final readonly class RefusalProblems
{
    public function of(UseCaseRefused $refusal): ProblemResource
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
