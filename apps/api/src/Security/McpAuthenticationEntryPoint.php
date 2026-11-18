<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * A call to /mcp without a valid token: 401, naming where the AI finds the authorization server (RFC 9728), the
 * only answer on which Claude starts its sign-in.
 */
final readonly class McpAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        #[Autowire(param: 'app_api_host')]
        private string $apiHost,
    ) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $metadata = sprintf('https://%s/.well-known/oauth-protected-resource/mcp', $this->apiHost);

        return new JsonResponse(['error' => 'invalid_token'], Response::HTTP_UNAUTHORIZED, [
            'WWW-Authenticate' => sprintf('Bearer error="invalid_token", resource_metadata="%s"', $metadata),
        ]);
    }
}
