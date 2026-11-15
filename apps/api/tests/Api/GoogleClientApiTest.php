<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;

/**
 * The client of Google an administrator configures for their account (ADR 0125): its id shown, its secret never, the
 * redirect URI to declare at Google; the other members neither see nor change it.
 */
#[Group('api')]
class GoogleClientApiTest extends ApiTestCase
{
    private const string THE_CLIENT = '/api/me/organization/clients/google';
    private const string REDIRECT_URI = 'https://front.test.prospecter.io/settings/connectors/google';

    protected function setUp(): void
    {
        parent::setUp();
        $this->aMemberOfTheFixtures('administrator', Role::User, Role::Admin);
    }

    public function testAnAdministratorSeesTheClientOfTheAccountWithoutItsSecret(): void
    {
        $this->loginAnAdministrator();

        $client = $this->requestJson('GET', self::THE_CLIENT);

        $this->assertResponseIsSuccessful();
        $this->assertSame([
            'clientId' => 'client-of-the-double',
            'secretSet' => true,
            'redirectUri' => self::REDIRECT_URI,
            '_links' => [
                'self' => ['href' => self::THE_CLIENT, 'method' => 'GET'],
                'configure' => ['href' => self::THE_CLIENT, 'method' => 'PATCH'],
                'remove' => ['href' => self::THE_CLIENT, 'method' => 'DELETE'],
            ],
        ], $client);
        $this->assertStringNotContainsString('secret-of-the-double', (string) $this->client->getResponse()->getContent());
    }

    public function testAnAdministratorConfiguresAnotherClient(): void
    {
        $this->loginAnAdministrator();

        $client = $this->requestJson('PATCH', self::THE_CLIENT, ['clientId' => 'client-of-solean', 'clientSecret' => 'secret-of-solean']);

        $this->assertResponseIsSuccessful();
        $this->assertSame(['client-of-solean', true], [$client['clientId'], $client['secretSet']]);
        $this->assertStringNotContainsString('secret-of-solean', (string) $this->client->getResponse()->getContent());
        $this->assertSame('client-of-solean', $this->requestJson('GET', self::THE_CLIENT)['clientId']);
        $this->assertStringContainsString('client_id=client-of-solean', $this->requestJson('POST', '/api/me/connections/google/authorizations')['address']);
    }

    public function testAClientNeedsItsIdAndItsSecret(): void
    {
        $this->loginAnAdministrator();

        $problem = $this->requestJson('PATCH', self::THE_CLIENT, ['clientId' => '', 'clientSecret' => '']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['clientId', 'clientSecret'], $this->violatedFields($problem));
        $this->assertSame('client-of-the-double', $this->requestJson('GET', self::THE_CLIENT)['clientId']);
    }

    public function testAnAdministratorRemovesTheClient(): void
    {
        $this->loginAnAdministrator();

        $client = $this->requestJson('DELETE', self::THE_CLIENT);

        $this->assertResponseIsSuccessful();
        $this->assertSame([
            'clientId' => null,
            'secretSet' => false,
            'redirectUri' => self::REDIRECT_URI,
            '_links' => [
                'self' => ['href' => self::THE_CLIENT, 'method' => 'GET'],
                'configure' => ['href' => self::THE_CLIENT, 'method' => 'PATCH'],
            ],
        ], $client);
        $this->assertArrayNotHasKey('connectGoogle', $this->requestJson('GET', '/api/me/connections')['_links']);
    }

    public function testNoClientToRemove(): void
    {
        $this->loginAnAdministrator();
        $this->requestJson('DELETE', self::THE_CLIENT);

        $problem = $this->requestJson('DELETE', self::THE_CLIENT);

        $this->assertResponseStatusCodeSame(404);
        $this->assertSame('noClient', $problem['reason']);
    }

    #[DataProvider('gesturesOfAMemberWhoIsNoAdministrator')]
    public function testAMemberWhoIsNoAdministratorNeitherSeesNorChangesTheClient(string $member, string $method): void
    {
        $this->login($member);

        $this->requestJson($method, self::THE_CLIENT, 'PATCH' === $method ? ['clientId' => 'client-of-solean', 'clientSecret' => 'secret-of-solean'] : null);

        $this->assertResponseStatusCodeSame(403);
        $this->loginAnAdministrator();
        $this->assertSame('client-of-the-double', $this->requestJson('GET', self::THE_CLIENT)['clientId']);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function gesturesOfAMemberWhoIsNoAdministrator(): iterable
    {
        foreach (['a prospector with the personal data' => 'prospector-user', 'a prospector without the personal data' => 'assistant-user'] as $who => $member) {
            foreach (['sees' => 'GET', 'configures' => 'PATCH', 'removes' => 'DELETE'] as $gesture => $method) {
                yield sprintf('%s %s', $who, $gesture) => [$member, $method];
            }
        }
    }

    public function testTheConnectionsLinkTheClientForAnAdministratorOnly(): void
    {
        $this->loginAnAdministrator();
        $this->assertSame(['href' => self::THE_CLIENT, 'method' => 'GET'], $this->requestJson('GET', '/api/me/connections')['_links']['googleClient']);

        $this->login('prospector-user');
        $this->assertArrayNotHasKey('googleClient', $this->requestJson('GET', '/api/me/connections')['_links']);
    }

    public function testAnAnonymousVisitorSeesNoClient(): void
    {
        $this->requestJson('GET', self::THE_CLIENT);

        $this->assertResponseStatusCodeSame(401);
    }

    private function loginAnAdministrator(): void
    {
        $this->login('administrator');
    }
}
