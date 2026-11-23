<?php

declare(strict_types=1);

namespace App\Security;

use App\Shared\Resource\ProblemResource;
use App\Shared\Typed;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The problem that a member reads when they are denied an intent. It is a 404 for another account's resource, so they do not even
 * learn that it exists (ADR 0081). It is a 403 for a role or a right they lack.
 */
final readonly class AccessProblems
{
    /** Named the way a domain refusal would be, so that the SPA words it (ADR 0073). */
    public const string ROLE_NOT_ALLOWED = 'roleNotAllowed';

    public function of(AccessDeniedException $denial): ProblemResource
    {
        $unknown = $this->unknownReasonOf($denial);

        return null === $unknown
            ? new ProblemResource('Forbidden', Response::HTTP_FORBIDDEN, 'Your role does not allow this action.', reason: self::ROLE_NOT_ALLOWED)
            : new ProblemResource('Not Found', Response::HTTP_NOT_FOUND, 'Unknown resource.', reason: $unknown);
    }

    private function unknownReasonOf(AccessDeniedException $denial): ?string
    {
        $vote = array_find($denial->getAccessDecision()->votes ?? [], static fn (Vote $vote): bool => isset($vote->extraData[AccountResourceVoter::UNKNOWN]));

        return Typed::stringOrNull($vote?->extraData[AccountResourceVoter::UNKNOWN]);
    }
}
