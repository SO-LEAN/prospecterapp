<?php

namespace App\Tests;

/**
 * The SPA signs a member in with a JSON POST, then opens again the authorization request the sign-in
 * interrupted (ADR 0037).
 */
class SignInTest extends AuthTestCase
{
    private const string AUTHORIZE = '/authorize?response_type=code&client_id=prospecter-front';

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->setServerParameter('REMOTE_ADDR', $this->anAddressOfItsOwn());
    }

    public function testAnAnonymousAuthorizationRequestGoesToTheSignInScreenOfTheSpa(): void
    {
        $this->client->request('GET', self::AUTHORIZE);

        $this->assertResponseRedirects('http://test.prospecter.io/auth/sign-in');
    }

    public function testSigningInResumesTheInterruptedAuthorizationRequest(): void
    {
        $this->client->request('GET', self::AUTHORIZE);

        $signedIn = $this->signInFromTheSpa('prospector@solean-it.io', 'password');

        $this->assertResponseIsSuccessful();
        $this->assertSame('http://api.test.prospecter.io/authorize?client_id=prospecter-front&response_type=code', $signedIn['resume']);
    }

    public function testWithoutAnInterruptedRequestTheSpaStartsOne(): void
    {
        $signedIn = $this->signInFromTheSpa('prospector@solean-it.io', 'password');

        $this->assertResponseIsSuccessful();
        $this->assertNull($signedIn['resume']);
    }

    public function testTheSessionKeepsTheMemberSignedInForTheAuthorizationRequest(): void
    {
        $this->signInFromTheSpa('prospector@solean-it.io', 'password');

        $this->client->request('GET', self::AUTHORIZE);

        $this->assertResponseStatusCodeSame(400, 'past the sign-in: the request is now checked by the OAuth server (no PKCE here)');
    }

    public function testAWrongPasswordIsRefused(): void
    {
        $problem = $this->signInFromTheSpa('prospector@solean-it.io', 'wrong password');

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
        $this->assertSame('Wrong login email or password.', $problem['detail']);
    }

    public function testAnUnknownLoginEmailIsRefusedTheSameWay(): void
    {
        $problem = $this->signInFromTheSpa('nobody@solean-it.io', 'password');

        $this->assertResponseStatusCodeSame(401);
        $this->assertSame('Wrong login email or password.', $problem['detail']);
    }

    public function testCredentialsThatAreNotJsonAreRefused(): void
    {
        $this->client->request('POST', '/login', server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ORIGIN' => 'http://test.prospecter.io'], content: 'email=prospector@solean-it.io&password=password');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testASignInFromAnotherSiteIsRefused(): void
    {
        $this->signInFromTheSpa('prospector@solean-it.io', 'password', origin: 'https://evil.example');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testTooManyFailedAttemptsAreThrottled(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->signInFromTheSpa('prospector@solean-it.io', 'wrong password');
            $this->assertResponseStatusCodeSame(401, 'attempt '.$attempt);
        }
        $this->signInFromTheSpa('prospector@solean-it.io', 'password');

        $this->assertResponseStatusCodeSame(429);
    }

    public function testLogoutGoesBackToTheSpa(): void
    {
        $this->signIn();

        $this->client->request('GET', '/logout');

        $this->assertResponseRedirects('http://test.prospecter.io');
    }

    /**
     * @return array<string, mixed>
     */
    private function signInFromTheSpa(string $loginEmail, string $password, string $origin = 'http://test.prospecter.io'): array
    {
        $this->client->request('POST', '/login', server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ORIGIN' => $origin], content: json_encode(['email' => $loginEmail, 'password' => $password], JSON_THROW_ON_ERROR));

        return $this->responseData();
    }

    /**
     * Failed attempts are counted by address and login email, in a cache that outlives a test: each test signs
     * in from an address of its own.
     */
    private function anAddressOfItsOwn(): string
    {
        return sprintf('10.%d.%d.%d', random_int(0, 255), random_int(0, 255), random_int(1, 254));
    }
}
