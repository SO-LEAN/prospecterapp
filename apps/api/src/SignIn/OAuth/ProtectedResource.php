<?php

declare(strict_types=1);

namespace App\SignIn\OAuth;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * The MCP server as the AIs know it (RFC 9728). It gives the server's address exactly as the member types it in their
 * AI, the server that signs them in (the API itself), and where its metadata are. The addresses use the API's host,
 * without the port that a worktree serves its stack on.
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
