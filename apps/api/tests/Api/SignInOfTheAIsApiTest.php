<?php

declare(strict_types=1);

namespace Tests\App\Api;

use App\Demo\DemoCredentials;
use App\Demo\DemoData;
use App\Shared\Typed;
use App\SignIn\UserPool;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpClient\HttpClient;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\CallsTheMcpServer;
use Tests\App\Double\ClientsOfTheAIs;

/**
 * An AI signs its member in on Cognito through the facade of the API, keeping nothing (ADR 0157): the sign-in page of
 * Cognito for the client of the AIs, then a code of the facade for the AI, exchanged for the tokens of Cognito.
 */
#[Group('api')]
class SignInOfTheAIsApiTest extends ApiTestCase
{
    use CallsTheMcpServer;

    private const int POLLING_INTERVAL_IN_MICROSECONDS = 10_000;
    private const string CALLBACK = 'https://api.test.prospecter.io/oauth/callback';
    private const string PROSPECTOR = 'prospector@solean-it.io';
    private const string VERIFIER_OF_THE_AI = 'the-verifier-of-the-ai-long-enough-to-be-a-verifier-of-pkce';

    public function testTheAiSendsItsMemberToTheSignInPageOfCognito(): void
    {
        $this->authorize();

        $this->assertResponseStatusCodeSame(302);
        $signInPage = (string) $this->client->getResponse()->headers->get('Location');
        $this->assertStringStartsWith('http://ministack-test:4566/oauth2/authorize?', $signInPage);
        parse_str((string) parse_url($signInPage, PHP_URL_QUERY), $query);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame(self::service(UserPool::class)->mcpClientId(), $query['client_id']);
        $this->assertSame(self::CALLBACK, $query['redirect_uri']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertNotSame(self::challengeOf(self::VERIFIER_OF_THE_AI), $query['code_challenge']);
    }

    public function testARedirectTheClientDoesNotDeclareIsRefusedWithoutRedirecting(): void
    {
        $this->authorize(['redirect_uri' => 'https://evil.example/callback']);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
        $this->assertFalse($this->client->getResponse()->headers->has('Location'));
    }

    public function testAClientWhoseDocumentIsOnAPrivateNetworkIsRefused(): void
    {
        $this->authorize(['client_id' => ClientsOfTheAIs::ON_A_PRIVATE_NETWORK]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testAnAiWithoutAProofKeyIsSentBackRefused(): void
    {
        $this->authorize(['code_challenge_method' => 'plain']);

        $this->assertResponseRedirects(ClientsOfTheAIs::CLAUDE_WEB_CALLBACK.'?'.http_build_query(['error' => 'invalid_request', 'error_description' => 'A code challenge S256 is required.', 'state' => 'the state of the AI']));
    }

    public function testAnAiAskingAnotherResponseThanACodeIsSentBackRefused(): void
    {
        $this->authorize(['response_type' => 'token']);

        $this->assertResponseRedirects(ClientsOfTheAIs::CLAUDE_WEB_CALLBACK.'?'.http_build_query(['error' => 'unsupported_response_type', 'error_description' => 'Only the code response type is supported.', 'state' => 'the state of the AI']));
    }

    public function testAnAiAskingAnotherResourceThanTheMcpServerIsSentBackRefused(): void
    {
        $this->authorize(['resource' => 'https://elsewhere.example/mcp']);

        $this->assertResponseRedirects(ClientsOfTheAIs::CLAUDE_WEB_CALLBACK.'?'.http_build_query(['error' => 'invalid_target', 'error_description' => 'The only resource is the Prospecter MCP server.', 'state' => 'the state of the AI']));
    }

    public function testCognitoSendsTheMemberBackToTheAiWithACodeOfTheFacade(): void
    {
        $this->authorize();
        $this->client->request('GET', $this->signInOnCognito());

        $this->assertResponseStatusCodeSame(302);
        $backToTheAi = (string) $this->client->getResponse()->headers->get('Location');
        $this->assertStringStartsWith(ClientsOfTheAIs::CLAUDE_WEB_CALLBACK.'?', $backToTheAi);
        parse_str((string) parse_url($backToTheAi, PHP_URL_QUERY), $query);
        $this->assertSame('the state of the AI', $query['state']);
        $this->assertNotEmpty($query['code']);
    }

    public function testAComingBackTheFacadeDidNotSealIsRefusedWithoutRedirecting(): void
    {
        $this->client->request('GET', '/oauth/callback?'.http_build_query(['code' => 'a code', 'state' => 'not sealed by the facade']));

        $this->assertResponseStatusCodeSame(400);
        $this->assertFalse($this->client->getResponse()->headers->has('Location'));
    }

    public function testAMemberNotSigningInOnCognitoIsSentBackToTheAiRefused(): void
    {
        $this->authorize();
        parse_str((string) parse_url((string) $this->client->getResponse()->headers->get('Location'), PHP_URL_QUERY), $signInPage);

        $this->client->request('GET', '/oauth/callback?'.http_build_query(['error' => 'access_denied', 'state' => $signInPage['state']]));

        $this->assertResponseRedirects(ClientsOfTheAIs::CLAUDE_WEB_CALLBACK.'?'.http_build_query(['error' => 'access_denied', 'state' => 'the state of the AI']));
    }

    public function testTheAiExchangesItsCodeForTheTokensOfCognito(): void
    {
        $tokens = $this->exchange($this->codeOfTheFacade());

        $this->assertResponseIsSuccessful();
        $this->assertSame('Bearer', $tokens['token_type']);
        $this->assertNotEmpty($tokens['access_token']);
        $this->assertNotEmpty($tokens['refresh_token']);
    }

    public function testTheAiCallsTheMcpServerOnBehalfOfItsMember(): void
    {
        $tokens = $this->exchange($this->codeOfTheFacade());
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.self::stringAt($tokens, 'access_token'));

        $result = $this->callWithoutHandshake('get-me');

        $this->assertSame('Camille Martin', self::valueAt($result, 'structuredContent', 'member'));
    }

    public function testTheTokensOfAnAiDoNotOpenTheApiOfTheApp(): void
    {
        $tokens = $this->exchange($this->codeOfTheFacade());
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.self::stringAt($tokens, 'access_token'));

        $this->requestJson('GET', '/api/me');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testACodeServesOnce(): void
    {
        $code = $this->codeOfTheFacade();
        $this->exchange($code);

        $refusal = $this->exchange($code);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame('invalid_grant', $refusal['error']);
    }

    public function testAVerifierNotAnsweringTheChallengeOfTheAiIsRefused(): void
    {
        $refusal = $this->exchange($this->codeOfTheFacade(), ['code_verifier' => 'the-verifier-of-another-request-long-enough-for-pkce']);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame('invalid_grant', $refusal['error']);
    }

    public function testACodeGivenToAnotherClientIsRefused(): void
    {
        $refusal = $this->exchange($this->codeOfTheFacade(), ['client_id' => ClientsOfTheAIs::CLAUDE_CODE]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame('invalid_grant', $refusal['error']);
    }

    public function testACodeGivenForAnotherRedirectIsRefused(): void
    {
        $refusal = $this->exchange($this->codeOfTheFacade(), ['redirect_uri' => 'http://localhost/callback']);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame('invalid_grant', $refusal['error']);
    }

    public function testACodeTheFacadeDidNotGiveIsRefused(): void
    {
        $refusal = $this->exchange('not a code of the facade');

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame('invalid_grant', $refusal['error']);
    }

    public function testAnExpiredTokenIsRenewedWithoutSigningInAgain(): void
    {
        $tokens = $this->exchange($this->codeOfTheFacade());

        $renewed = $this->refresh(self::stringAt($tokens, 'refresh_token'));

        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($renewed['access_token']);
    }

    public function testSigningOutOfTheAppLeavesTheAiSignedIn(): void
    {
        $tokens = $this->exchange($this->codeOfTheFacade());
        $pool = self::service(UserPool::class);
        $ofTheApp = $this->tokensOf(DemoData::PROSPECTOR);

        self::service(CognitoIdentityProviderClient::class)->revokeToken(['ClientId' => $pool->clientId(), 'Token' => $ofTheApp['RefreshToken']]);

        $this->refresh(self::stringAt($tokens, 'refresh_token'));
        $this->assertResponseIsSuccessful();
    }

    public function testSigningOutEverywhereSignsTheAiOutToo(): void
    {
        $tokens = $this->exchange($this->codeOfTheFacade());
        self::waitForTheNextSecond();

        self::service(CognitoIdentityProviderClient::class)->globalSignOut(['AccessToken' => $this->tokensOf(DemoData::PROSPECTOR)['AccessToken']]);

        $this->assertSame('invalid_grant', $this->refresh(self::stringAt($tokens, 'refresh_token'))['error']);
    }

    public function testAGrantTheFacadeDoesNotKnowIsRefused(): void
    {
        $this->client->request('POST', '/oauth/token', ['grant_type' => 'client_credentials']);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame('unsupported_grant_type', self::valueAt($this->responseData(), 'error'));
    }

    /**
     * @return array<string, mixed>
     */
    private function refresh(string $refreshToken): array
    {
        $this->client->request('POST', '/oauth/token', ['grant_type' => 'refresh_token', 'refresh_token' => $refreshToken, 'client_id' => ClientsOfTheAIs::CLAUDE_WEB]);

        return $this->answerOfTheTokenEndpoint();
    }

    /**
     * The code the facade gives the AI once its member signed in on Cognito.
     */
    private function codeOfTheFacade(): string
    {
        $this->authorize();
        $this->client->request('GET', $this->signInOnCognito());
        parse_str((string) parse_url((string) $this->client->getResponse()->headers->get('Location'), PHP_URL_QUERY), $backToTheAi);

        return Typed::string($backToTheAi['code']);
    }

    /**
     * @param array<string, string> $changes
     *
     * @return array<string, mixed>
     */
    private function exchange(string $code, array $changes = []): array
    {
        $this->client->request('POST', '/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => ClientsOfTheAIs::CLAUDE_WEB,
            'redirect_uri' => ClientsOfTheAIs::CLAUDE_WEB_CALLBACK,
            'code_verifier' => self::VERIFIER_OF_THE_AI,
            ...$changes,
        ]);

        return $this->answerOfTheTokenEndpoint();
    }

    /**
     * What the token endpoint answers, in JSON whether it grants or refuses (RFC 6749, 5.1 and 5.2).
     *
     * @return array<string, mixed>
     */
    private function answerOfTheTokenEndpoint(): array
    {
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        return $this->responseData();
    }

    /**
     * The member signs in on the sign-in page of Cognito the facade sent them to, as their browser does: where Cognito
     * sends them back.
     */
    private function signInOnCognito(string $email = self::PROSPECTOR): string
    {
        parse_str((string) parse_url((string) $this->client->getResponse()->headers->get('Location'), PHP_URL_QUERY), $authorization);
        $signedIn = HttpClient::create()->request('POST', 'http://ministack-test:4566/login', [
            'body' => [...$authorization, 'username' => $email, 'password' => DemoCredentials::PASSWORD],
            'max_redirects' => 0,
        ]);

        $callback = $signedIn->getHeaders(false)['location'][0] ?? throw new \LogicException('Cognito did not sign the member in: '.$signedIn->getContent(false));
        $this->assertStringStartsWith(self::CALLBACK.'?', $callback);

        return $callback;
    }

    /**
     * @param array<string, string> $changes
     */
    private function authorize(array $changes = []): void
    {
        $this->client->request('GET', '/oauth/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => ClientsOfTheAIs::CLAUDE_WEB,
            'redirect_uri' => ClientsOfTheAIs::CLAUDE_WEB_CALLBACK,
            'code_challenge' => self::challengeOf(self::VERIFIER_OF_THE_AI),
            'code_challenge_method' => 'S256',
            'state' => 'the state of the AI',
            'resource' => 'https://api.test.prospecter.io/mcp',
            ...$changes,
        ]));
    }

    private static function challengeOf(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    /**
     * MiniStack revokes the tokens issued before the second of a sign-out, not those issued within it.
     */
    private static function waitForTheNextSecond(): void
    {
        $second = time(); // @phpstan-ignore prospecter.realClock (MiniStack revokes the tokens on the real clock)

        while (time() === $second) { // @phpstan-ignore prospecter.realClock (MiniStack revokes the tokens on the real clock)
            usleep(self::POLLING_INTERVAL_IN_MICROSECONDS);
        }
    }
}
