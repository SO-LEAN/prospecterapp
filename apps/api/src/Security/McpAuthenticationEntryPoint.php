<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * A call to the MCP server without a valid access token: 401 naming the metadata of the protected resource, where
 * the AI finds the server to sign its member in on (RFC 9728), the only answer on which Claude starts its sign-in; a
 * token refused says so (RFC 6750).
 */
final readonly class McpAuthenticationEntryPoint implements AuthenticationEntryPointInterface, AuthenticationFailureHandlerInterface
{
    public function __construct(
        #[Autowire(param: 'app_api_host')]
        private string $apiHost,
    ) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->unauthorized(sprintf('Bearer resource_metadata="%s"', $this->metadata()));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return $this->unauthorized(sprintf('Bearer error="invalid_token", resource_metadata="%s"', $this->metadata()));
    }

    private function unauthorized(string $challenge): Response
    {
        return new Response(status: Response::HTTP_UNAUTHORIZED, headers: ['WWW-Authenticate' => $challenge]);
    }

    private function metadata(): string
    {
        return sprintf('https://%s/.well-known/oauth-protected-resource/mcp', $this->apiHost);
    }
}
