<?php

namespace Tests\App\Api;

use App\SignIn\UserPool;
use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\ClientsOfTheAIs;

/**
 * An AI signs its member in on Cognito through the facade of the API, keeping nothing (ADR 0157): the sign-in page of
 * Cognito for the client of the AIs, then a code of the facade for the AI, exchanged for the tokens of Cognito.
 */
#[Group('api')]
class SignInOfTheAIsApiTest extends ApiTestCase
{
    private const string CALLBACK = 'https://api.test.prospecter.io/oauth/callback';
    private const string VERIFIER_OF_THE_AI = 'the-verifier-of-the-ai-long-enough-to-be-a-verifier-of-pkce';

    public function testTheAiSendsItsMemberToTheSignInPageOfCognito(): void
    {
        $this->authorize();

        $this->assertResponseStatusCodeSame(302);
        $signInPage = (string) $this->client->getResponse()->headers->get('Location');
        $this->assertStringStartsWith('http://ministack-test:4566/oauth2/authorize?', $signInPage);
        parse_str((string) parse_url($signInPage, PHP_URL_QUERY), $query);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame(self::getContainer()->get(UserPool::class)->mcpClientId(), $query['client_id']);
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

        $this->assertResponseRedirects(ClientsOfTheAIs::CLAUDE_WEB_CALLBACK.'?'.http_build_query(['error' => 'unsupported_response_type', 'error_description' => 'Only a code is given.', 'state' => 'the state of the AI']));
    }

    public function testAnAiAskingAnotherResourceThanTheMcpServerIsSentBackRefused(): void
    {
        $this->authorize(['resource' => 'https://elsewhere.example/mcp']);

        $this->assertResponseRedirects(ClientsOfTheAIs::CLAUDE_WEB_CALLBACK.'?'.http_build_query(['error' => 'invalid_target', 'error_description' => 'The resource is the MCP server of Prospecter.', 'state' => 'the state of the AI']));
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
}
