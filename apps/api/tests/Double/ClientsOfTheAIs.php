<?php

namespace Tests\App\Double;

use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * The Client ID Metadata Documents Anthropic publishes for Claude Code and Claude web, as the tests see them (ADR 0157),
 * and the names of their host, resolved without the network: on its address of Anthropic, an address of a private
 * network for a host a test names so.
 */
final class ClientsOfTheAIs
{
    public const string CLAUDE_CODE = 'https://claude.ai/oauth/claude-code-client-metadata';
    public const string CLAUDE_WEB = 'https://claude.ai/oauth/mcp-oauth-client-metadata';
    public const string CLAUDE_WEB_CALLBACK = 'https://claude.ai/api/mcp/auth_callback';
    public const string ON_A_PRIVATE_NETWORK = 'https://internal.example/client';

    private const array ADDRESSES = ['claude.ai' => '160.79.104.10', 'internal.example' => '10.0.0.7'];

    public function serves(string $url): bool
    {
        return in_array($url, [self::CLAUDE_CODE, self::CLAUDE_WEB, self::ON_A_PRIVATE_NETWORK], true);
    }

    public function answer(string $url): ResponseInterface
    {
        return new JsonMockResponse(['client_id' => $url, 'client_name' => 'Claude', 'redirect_uris' => match ($url) {
            self::CLAUDE_CODE => ['http://localhost/callback', 'http://127.0.0.1/callback'],
            default => [self::CLAUDE_WEB_CALLBACK],
        }]);
    }

    /**
     * The address a host name resolves to, as `DnsResolvingHttpClient` asks it.
     */
    public function __invoke(string $host): ?string
    {
        return self::ADDRESSES[$host] ?? null;
    }
}
