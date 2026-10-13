<?php

namespace Tests\App\Api;

use App\DataFixtures\OAuthClientFixtures;
use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;

#[Group('api')]
class OAuthFlowTest extends ApiTestCase
{
    public function testApiRequiresAnAccessToken(): void
    {
        $problem = $this->requestJson('GET', '/api/me');

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('WWW-Authenticate', 'Bearer');
        $this->assertSame('Unauthorized', $problem['title']);
    }

    public function testAuthorizeRedirectsAnonymousUsersToTheLoginPage(): void
    {
        $this->client->request('GET', '/authorize?response_type=code&client_id='.OAuthClientFixtures::FRONT_CLIENT_ID);

        $this->assertResponseRedirects('http://api.test.prospecter.io/login');
    }

    public function testAuthorizationCodeWithPkceGivesAccessToTheApi(): void
    {
        parent::login();
        $tokens = $this->obtainTokens();

        $this->assertSame('Bearer', $tokens['token_type']);
        $this->assertNotEmpty($tokens['refresh_token']);

        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$tokens['access_token']);
        $account = $this->requestJson('GET', '/api/me');

        $this->assertResponseIsSuccessful();
        $this->assertSame('prospector', $account['userName']);
    }

    public function testRefreshTokenIsRotated(): void
    {
        parent::login();
        $tokens = $this->obtainTokens();

        $this->client->request('POST', '/token', [
            'grant_type' => 'refresh_token',
            'client_id' => OAuthClientFixtures::FRONT_CLIENT_ID,
            'refresh_token' => $tokens['refresh_token'],
        ]);
        $refreshed = $this->responseData();

        $this->assertResponseIsSuccessful();
        $this->assertNotSame($tokens['refresh_token'], $refreshed['refresh_token']);

        $this->client->request('POST', '/token', [
            'grant_type' => 'refresh_token',
            'client_id' => OAuthClientFixtures::FRONT_CLIENT_ID,
            'refresh_token' => $tokens['refresh_token'],
        ]);
        $this->assertResponseStatusCodeSame(400, 'a used refresh token is revoked');
    }

    public function testCodeWithoutPkceIsRefused(): void
    {
        parent::login();

        $this->client->request('GET', '/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => OAuthClientFixtures::FRONT_CLIENT_ID,
            'redirect_uri' => $this->redirectUri(),
            'scope' => 'api',
            'state' => 'x',
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame('invalid_request', $this->responseData()['error']);
    }
}
