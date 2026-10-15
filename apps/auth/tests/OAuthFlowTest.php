<?php

namespace App\Tests;

use App\DataFixtures\CredentialsFixtures;
use App\DataFixtures\OAuthClientFixtures;
use PHPUnit\Framework\Attributes\DataProvider;

class OAuthFlowTest extends AuthTestCase
{
    public function testWhatISignInWithRequiresAnAccessToken(): void
    {
        $problem = $this->requestJson('GET', '/auth/me');

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('WWW-Authenticate', 'Bearer');
        $this->assertSame('Unauthorized', $problem['title']);
    }

    public function testTheAccessTokenNamesTheMemberAndOpensWhatISignInWith(): void
    {
        $tokens = $this->signInWithAToken();

        $this->assertSame('Bearer', $tokens['token_type']);
        $this->assertNotEmpty($tokens['refresh_token']);
        $this->assertSame(CredentialsFixtures::PROSPECTOR, $this->subjectOf($tokens['access_token']), 'the API finds the member by its id');
        $this->assertSame('prospector@solean-it.io', $this->requestJson('GET', '/auth/me')['loginEmail']);
    }

    public function testRefreshTokenIsRotated(): void
    {
        $this->signIn();
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

    /**
     * @return iterable<string, array{string}>
     */
    public static function grantsNoClientNeeds(): iterable
    {
        yield 'client credentials' => ['client_credentials'];
        yield 'password' => ['password'];
    }

    #[DataProvider('grantsNoClientNeeds')]
    public function testOnlyTheGrantsOfTheSpaAreOffered(string $grant): void
    {
        $this->client->request('POST', '/token', ['grant_type' => $grant, 'client_id' => OAuthClientFixtures::FRONT_CLIENT_ID, 'username' => 'prospector@solean-it.io', 'password' => 'password']);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame('unsupported_grant_type', $this->responseData()['error']);
    }

    public function testCodeWithoutPkceIsRefused(): void
    {
        $this->signIn();

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

    private function subjectOf(string $jwt): string
    {
        return json_decode(base64_decode(strtr(explode('.', $jwt)[1], '-_', '+/')), true)['sub'];
    }
}
