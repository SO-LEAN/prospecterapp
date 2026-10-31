<?php

namespace App\Security;

use App\Shared\Resource\ProblemResource;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * A signed-in member denied an intention gets a problem+json, like every other API error: a 404 for a resource of
 * another account, which they do not even learn exists (ADR 0081), a 403 for a role or a right they lack.
 */
final readonly class ApiAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(private NormalizerInterface $normalizer)
    {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): JsonResponse
    {
        $unknown = $this->unknownReasonOf($accessDeniedException);
        $problem = null === $unknown
            ? new ProblemResource('Forbidden', Response::HTTP_FORBIDDEN, 'Your role does not allow this action.')
            : new ProblemResource('Not Found', Response::HTTP_NOT_FOUND, 'Unknown resource.', reason: $unknown);

        return new JsonResponse($this->normalizer->normalize($problem), $problem->status, ['Content-Type' => 'application/problem+json']);
    }

    private function unknownReasonOf(AccessDeniedException $denial): ?string
    {
        $vote = array_find($denial->getAccessDecision()->votes ?? [], static fn (Vote $vote): bool => isset($vote->extraData[AccountResourceVoter::UNKNOWN]));

        return $vote?->extraData[AccountResourceVoter::UNKNOWN];
    }
}
