<?php

namespace App\Security;

use App\Api\Resource\ProblemResource;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * API calls without a valid access token get a problem+json 401, like every other API error.
 */
final readonly class ApiAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(private NormalizerInterface $normalizer)
    {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): JsonResponse
    {
        $problem = new ProblemResource('Unauthorized', Response::HTTP_UNAUTHORIZED, 'A valid OAuth 2 access token is required.');

        return new JsonResponse($this->normalizer->normalize($problem), $problem->status, [
            'Content-Type' => 'application/problem+json',
            'WWW-Authenticate' => 'Bearer',
        ]);
    }
}
