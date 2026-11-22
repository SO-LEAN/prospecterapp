<?php

declare(strict_types=1);

namespace Tests\App\Api;

use App\Demo\DemoData;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Symfony\Component\Clock\MockClock;
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

        $result = $this->callWithoutHandshake('get-me');

        $this->assertSame(['member' => 'Camille Martin', 'account' => 'SOLEAN IT', 'readsPersonalData' => true], $result['structuredContent']);
    }

    public function testAnAiHasTheRightsOfItsMemberWithoutThePersonalData(): void
    {
        $this->login('assistant-user');

        $result = $this->callWithoutHandshake('get-me');

        $this->assertFalse(self::valueAt($result, 'structuredContent', 'readsPersonalData'));
    }

    public function testAnAiOfAnAdministratorReadsThePersonalDataAsTheyDo(): void
    {
        $this->login($this->aMemberOfTheFixtures('admin-user', Role::User, Role::Admin));

        $result = $this->callWithoutHandshake('get-me');

        $this->assertTrue(self::valueAt($result, 'structuredContent', 'readsPersonalData'));
    }

    public function testKnowingWhomItServesChangesNothing(): void
    {
        $this->login();

        $whoAmI = array_find($this->everyToolListed(), static fn (array $tool): bool => 'get-me' === $tool['name']);
        $this->assertNotNull($whoAmI);
        $this->assertTrue(self::valueAt($whoAmI, 'annotations', 'readOnlyHint'));
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

        $result = $this->callWithHandshake('tools/call', ['name' => 'get-me', 'arguments' => new \stdClass()]);

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
        self::service(MockClock::class, 'app.token_clock')->modify('+2 hours');
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$token);

        $this->client->request('POST', '/mcp', server: ['CONTENT_TYPE' => 'application/json'], content: '{}');

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('WWW-Authenticate', sprintf('Bearer error="invalid_token", resource_metadata="%s"', self::METADATA));
    }
}
