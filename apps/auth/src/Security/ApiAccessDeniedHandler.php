<?php

namespace App\Security;

use App\Resource\ProblemResource;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * A signed-in user without the role of an endpoint gets a problem+json 403, like every other API error.
 */
final readonly class ApiAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(private NormalizerInterface $normalizer)
    {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): JsonResponse
    {
        $problem = new ProblemResource('Forbidden', Response::HTTP_FORBIDDEN, 'Your role does not allow this action.');

        return new JsonResponse($this->normalizer->normalize($problem), $problem->status, ['Content-Type' => 'application/problem+json']);
    }
}
