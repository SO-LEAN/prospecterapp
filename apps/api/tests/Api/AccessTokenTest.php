<?php

namespace Tests\App\Api;

use App\Demo\DemoData;
use App\SignIn\Credentials;
use App\SignIn\UserPool;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;

/**
 * The API trusts an access token of the user pool only (ADR 0052): signed with one of its keys, issued for the
 * SPA, an access token and not an id token, not expired, naming a member.
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
        $token = $this->tokensOf(DemoData::PROSPECTOR)['AccessToken'];
        self::getContainer()->get('app.token_clock')->modify('+2 hours');

        $this->withToken($token);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testATokenForAnotherClientIsRefused(): void
    {
        $this->withToken($this->tokensOf(DemoData::PROSPECTOR, $this->anotherClient())['AccessToken']);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAnIdTokenIsNotAnAccessToken(): void
    {
        $this->withToken($this->tokensOf(DemoData::PROSPECTOR)['IdToken']);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testATokenNotSignedByTheUserPoolIsRefused(): void
    {
        [$header, $payload] = explode('.', $this->tokensOf(DemoData::PROSPECTOR)['AccessToken']);

        $this->withToken($header.'.'.$payload.'.forged-signature');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testATokenNamingNoMemberIsRefused(): void
    {
        $stranger = '01900000-0000-7000-8000-0000000000ff';
        self::getContainer()->get(Credentials::class)->register($stranger, 'stranger@competitor.io', 'password');

        $this->withToken($this->tokensOf($stranger)['AccessToken']);

        $this->assertResponseStatusCodeSame(401);
    }

    private function withToken(string $token): void
    {
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$token);
        $this->requestJson('GET', '/api/me');
    }

    private function anotherClient(): string
    {
        return self::getContainer()->get(CognitoIdentityProviderClient::class)->createUserPoolClient([
            'UserPoolId' => self::getContainer()->get(UserPool::class)->id(),
            'ClientName' => 'another-client',
            'ExplicitAuthFlows' => ['ALLOW_ADMIN_USER_PASSWORD_AUTH'],
        ])['UserPoolClient']['ClientId'];
    }
}
