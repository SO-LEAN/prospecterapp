<?php

namespace Tests\App\Api;

use App\DataFixtures\AppFixtures;
use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;

/**
 * The API trusts an access token of the authorization server only: signed with its key, for the SPA, not
 * expired, naming a member.
 */
#[Group('api')]
class AccessTokenTest extends ApiTestCase
{
    public function testTheApiRequiresAnAccessToken(): void
    {
        $problem = $this->requestJson('GET', '/api/me');

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('WWW-Authenticate', 'Bearer');
        $this->assertSame('Unauthorized', $problem['title']);
    }

    public function testATokenNamingAMemberOpensTheApi(): void
    {
        $this->login();

        $this->requestJson('GET', '/api/me');

        $this->assertResponseIsSuccessful();
    }

    public function testAnExpiredTokenIsRefused(): void
    {
        $this->withToken($this->accessTokenOf(self::prospector(), expiresIn: '-1 minute'));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testATokenForAnotherClientIsRefused(): void
    {
        $this->withToken($this->accessTokenOf(self::prospector(), audience: 'another-client'));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testATokenNotSignedByTheAuthorizationServerIsRefused(): void
    {
        [$header, $payload] = explode('.', $this->accessTokenOf(self::prospector()));

        $this->withToken($header.'.'.$payload.'.forged-signature');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testATokenNamingNoMemberIsRefused(): void
    {
        $this->withToken($this->accessTokenOf('01900000-0000-7000-8000-0000000000ff'));

        $this->assertResponseStatusCodeSame(401);
    }

    private function withToken(string $token): void
    {
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$token);
        $this->requestJson('GET', '/api/me');
    }

    private static function prospector(): string
    {
        return AppFixtures::PROSPECTOR;
    }
}
