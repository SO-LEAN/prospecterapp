<?php

declare(strict_types=1);

namespace App\Shared;

use App\Shared\Resource\ProblemResource;
use App\Shared\Resource\ViolationResource;
use Prospecter\Core\SharedKernel\Application\Exception\ErrorType;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Symfony\Component\HttpFoundation\Response;

/**
 * Maps a use case's refusal to a problem by the refusal's type. An unknown resource (or one of another account) is a
 * 404. An intention that the resource's state does not offer (no link) is a 409. An intention the caller has no right
 * to is a 403. Any other business rule is a 422. Each problem carries the refusal's reason, and the client words it
 * (ADR 0073).
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
