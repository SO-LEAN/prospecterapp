<?php

namespace App\SignIn\OAuth;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * The MCP server as the AIs know it (RFC 9728): its address, exactly as the member types it in their AI, the server
 * signing them in, the API itself, and where its metadata say so; named by the host of the API, without the port a
 * worktree serves its stack on.
 */
final readonly class ProtectedResource
{
    public function __construct(
        #[Autowire(param: 'app_api_host')]
        private string $apiHost,
    ) {
    }

    public function resource(): string
    {
        return $this->authorizationServer().'/mcp';
    }

    public function authorizationServer(): string
    {
        return 'https://'.$this->apiHost;
    }

    public function metadata(): string
    {
        return $this->authorizationServer().'/.well-known/oauth-protected-resource/mcp';
    }
}
