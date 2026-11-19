<?php

namespace Tests\App\Api;

use App\Demo\DemoData;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\CallsTheMcpServer;

/**
 * An AI speaks to the MCP server of the API as the member whose access token of Cognito it carries, with their
 * rights (ADR 0156): without a valid token, it is sent to the metadata naming where it signs in (RFC 9728).
 */
#[Group('api')]
class McpApiTest extends ApiTestCase
{
    use CallsTheMcpServer;

    private const string REVISION_OF_THE_HANDSHAKE = '2025-11-25';

    private const string METADATA = 'https://api.test.prospecter.io/.well-known/oauth-protected-resource/mcp';

    public function testWithoutATokenTheAiIsSentToTheMetadataOfTheProtectedResource(): void
    {
        $this->client->request('POST', '/mcp', server: ['CONTENT_TYPE' => 'application/json'], content: '{}');

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('WWW-Authenticate', sprintf('Bearer resource_metadata="%s"', self::METADATA));
    }

    public function testAnAiSignedInKnowsWhomItServes(): void
    {
        $this->login();

        $result = $this->callWithoutHandshake('qui-suis-je');

        $this->assertSame(['member' => 'Camille Martin', 'account' => 'SOLEAN IT', 'readsPersonalData' => true], $result['structuredContent']);
    }

    public function testAnAiHasTheRightsOfItsMemberWithoutThePersonalData(): void
    {
        $this->login('assistant-user');

        $result = $this->callWithoutHandshake('qui-suis-je');

        $this->assertFalse($result['structuredContent']['readsPersonalData']);
    }

    public function testAnAiOfAnAdministratorReadsThePersonalDataAsTheyDo(): void
    {
        $this->login($this->aMemberOfTheFixtures('admin-user', Role::User, Role::Admin));

        $result = $this->callWithoutHandshake('qui-suis-je');

        $this->assertTrue($result['structuredContent']['readsPersonalData']);
    }

    public function testAClientOfTheHandshakeIsAnsweredTheSameWithoutSession(): void
    {
        $this->login();
        $this->callWithHandshake('initialize', [
            'protocolVersion' => self::REVISION_OF_THE_HANDSHAKE,
            'capabilities' => new \stdClass(),
            'clientInfo' => ['name' => 'a client of the handshake', 'version' => '1.0'],
        ]);
        $this->assertResponseNotHasHeader('Mcp-Session-Id');

        $result = $this->callWithHandshake('tools/call', ['name' => 'qui-suis-je', 'arguments' => new \stdClass()]);

        $this->assertSame(['member' => 'Camille Martin', 'account' => 'SOLEAN IT', 'readsPersonalData' => true], $result['structuredContent']);
        $this->assertResponseNotHasHeader('Mcp-Session-Id');
    }

    public function testTheServerOpensNoStreamOfItsOwn(): void
    {
        $this->login();

        $this->client->request('GET', '/mcp', server: ['HTTP_ACCEPT' => 'text/event-stream']);

        $this->assertResponseStatusCodeSame(405);
    }

    public function testAnExpiredTokenIsRefusedAsAnInvalidToken(): void
    {
        $token = $this->tokensOf(DemoData::PROSPECTOR)['AccessToken'];
        self::getContainer()->get('app.token_clock')->modify('+2 hours');
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$token);

        $this->client->request('POST', '/mcp', server: ['CONTENT_TYPE' => 'application/json'], content: '{}');

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('WWW-Authenticate', sprintf('Bearer error="invalid_token", resource_metadata="%s"', self::METADATA));
    }

    /**
     * A request of the revision of the handshake, its version in its header once initialized.
     *
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed> its result
     */
    private function callWithHandshake(string $method, array $params): array
    {
        $this->client->request('POST', '/mcp', server: [
            'HTTP_ACCEPT' => 'application/json, text/event-stream',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_MCP_PROTOCOL_VERSION' => self::REVISION_OF_THE_HANDSHAKE,
        ], content: json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params], JSON_THROW_ON_ERROR));

        return $this->resultOfTheCall();
    }
}
