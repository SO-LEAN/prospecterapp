<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;

/**
 * The API is the authorization server the AIs see, a facade over Cognito (RFC 8414, ADR 0157): public, read before
 * anyone is signed in.
 */
#[Group('api')]
class AuthorizationServerApiTest extends ApiTestCase
{
    public function testTheMetadataNameTheEndpointsOfTheFacade(): void
    {
        $metadata = $this->requestJson('GET', '/.well-known/oauth-authorization-server');

        $this->assertResponseIsSuccessful();
        $this->assertEquals([
            'issuer' => 'https://api.test.prospecter.io',
            'authorization_endpoint' => 'https://api.test.prospecter.io/oauth/authorize',
            'token_endpoint' => 'https://api.test.prospecter.io/oauth/token',
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'code_challenge_methods_supported' => ['S256'],
            'token_endpoint_auth_methods_supported' => ['none'],
            'client_id_metadata_document_supported' => true,
        ], $metadata);
    }
}
