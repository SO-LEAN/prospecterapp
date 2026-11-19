<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;

/**
 * Where an AI sent away by the MCP server finds the server to sign its member in on (RFC 9728, ADR 0157): public,
 * read before anyone is signed in.
 */
#[Group('api')]
class ProtectedResourceApiTest extends ApiTestCase
{
    public function testTheMetadataNameTheMcpServerAndTheAuthorizationServerOfTheApi(): void
    {
        $metadata = $this->requestJson('GET', '/.well-known/oauth-protected-resource/mcp');

        $this->assertResponseIsSuccessful();
        $this->assertSame('https://api.test.prospecter.io/mcp', $metadata['resource']);
        $this->assertSame(['https://api.test.prospecter.io'], $metadata['authorization_servers']);
    }

    public function testAClientLookingAtTheRootOfTheHostFindsThemToo(): void
    {
        $metadata = $this->requestJson('GET', '/.well-known/oauth-protected-resource');

        $this->assertSame('https://api.test.prospecter.io/mcp', $metadata['resource'] ?? null);
    }
}
