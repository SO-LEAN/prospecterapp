<?php

declare(strict_types=1);

namespace App\Security;

use App\SignIn\OAuth\ProtectedResource;
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
    public function __construct(private ProtectedResource $mcp)
    {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->unauthorized(sprintf('Bearer resource_metadata="%s"', $this->mcp->metadata()));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return $this->unauthorized(sprintf('Bearer error="invalid_token", resource_metadata="%s"', $this->mcp->metadata()));
    }

    private function unauthorized(string $challenge): Response
    {
        return new Response(status: Response::HTTP_UNAUTHORIZED, headers: ['WWW-Authenticate' => $challenge]);
    }
}
