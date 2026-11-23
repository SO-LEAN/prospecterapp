<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * A signed-in member who is denied an intent gets a problem+json, like every other API error. It is a 404 for another
 * account's resource, so they do not even learn that it exists (ADR 0081). It is a 403 for a role or a right they
 * lack.
 */
final readonly class ApiAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(private AccessProblems $problems, private NormalizerInterface $normalizer)
    {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): JsonResponse
    {
        $problem = $this->problems->of($accessDeniedException);

        return new JsonResponse($this->normalizer->normalize($problem), $problem->status, ['Content-Type' => 'application/problem+json']);
    }
}
