<?php

declare(strict_types=1);

namespace Tests\App\Api;

use App\Shared\Typed;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\ReadsResources;
use Tests\App\Double\RefusingSsm;

/**
 * The client of Google an administrator configures for their account (ADR 0125): its id shown, its secret never, the
 * redirect URI to declare at Google; the other members neither see nor change it.
 */
#[Group('api')]
class GoogleClientApiTest extends ApiTestCase
{
    use ReadsResources;

    private const string THE_CLIENT = '/api/me/organization/clients/google';
    private const string REDIRECT_URI = 'https://front.test.prospecter.io/settings/connectors/google';
    private const string GMAIL_NOTIFICATIONS = '/api/me/organization/clients/google/gmail-notifications';
    private const string TOPIC = 'projects/solean/topics/gmail';
    private const string PUSH_ACCOUNT = 'push@solean.iam.gserviceaccount.com';

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
            'gmailNotificationEndpoint' => $this->notificationEndpoint(),
            '_links' => [
                'self' => ['href' => self::THE_CLIENT, 'method' => 'GET'],
                'configure' => ['href' => self::THE_CLIENT, 'method' => 'PATCH'],
                'remove' => ['href' => self::THE_CLIENT, 'method' => 'DELETE'],
                'configureGmailNotifications' => ['href' => self::GMAIL_NOTIFICATIONS, 'method' => 'PATCH'],
            ],
        ], $client);
        $this->assertStringNotContainsString('secret-of-the-double', (string) $this->client->getResponse()->getContent());
    }

    public function testAnAdministratorNamesTheTopicGmailNotifiesThrough(): void
    {
        $this->loginAnAdministrator();

        $client = $this->requestJson('PATCH', self::GMAIL_NOTIFICATIONS, ['topic' => self::TOPIC, 'pushAccount' => self::PUSH_ACCOUNT]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(['topic' => self::TOPIC, 'pushAccount' => self::PUSH_ACCOUNT], $client['gmailNotifications']);
        $this->assertSame(['href' => self::GMAIL_NOTIFICATIONS, 'method' => 'DELETE'], self::valueAt($client, '_links', 'removeGmailNotifications'));
        $this->assertSame(self::TOPIC, self::valueAt($this->requestJson('GET', self::THE_CLIENT), 'gmailNotifications', 'topic'));
    }

    public function testAnAdministratorRemovesTheTopic(): void
    {
        $this->loginAnAdministrator();
        $this->requestJson('PATCH', self::GMAIL_NOTIFICATIONS, ['topic' => self::TOPIC, 'pushAccount' => self::PUSH_ACCOUNT]);

        $client = $this->requestJson('DELETE', self::GMAIL_NOTIFICATIONS);

        $this->assertResponseIsSuccessful();
        $this->assertArrayNotHasKey('gmailNotifications', $client);
        $this->assertArrayNotHasKey('removeGmailNotifications', self::recordAt($client, '_links'));
    }

    public function testATopicIsNamedInFull(): void
    {
        $this->loginAnAdministrator();

        $problem = $this->requestJson('PATCH', self::GMAIL_NOTIFICATIONS, ['topic' => 'gmail', 'pushAccount' => self::PUSH_ACCOUNT]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('invalidTopic', $problem['reason']);
    }

    public function testATopicNeedsItsAccountOfService(): void
    {
        $this->loginAnAdministrator();

        $this->requestJson('PATCH', self::GMAIL_NOTIFICATIONS, ['topic' => self::TOPIC, 'pushAccount' => 'no-address']);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testAnAdministratorConfiguresAnotherClient(): void
    {
        $this->loginAnAdministrator();

        $client = $this->requestJson('PATCH', self::THE_CLIENT, ['clientId' => 'client-of-solean', 'clientSecret' => 'secret-of-solean']);

        $this->assertResponseIsSuccessful();
        $this->assertSame(['client-of-solean', true], [$client['clientId'], $client['secretSet']]);
        $this->assertStringNotContainsString('secret-of-solean', (string) $this->client->getResponse()->getContent());
        $this->assertSame('client-of-solean', self::valueAt($this->requestJson('GET', self::THE_CLIENT), 'clientId'));
        $this->assertStringContainsString('client_id=client-of-solean', self::stringAt($this->requestJson('POST', '/api/me/connections/google/authorizations'), 'address'));
    }

    public function testAClientNeedsItsIdAndItsSecret(): void
    {
        $this->loginAnAdministrator();

        $problem = $this->requestJson('PATCH', self::THE_CLIENT, ['clientId' => '', 'clientSecret' => '']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['clientId', 'clientSecret'], $this->violatedFields($problem));
        $this->assertSame('client-of-the-double', self::valueAt($this->requestJson('GET', self::THE_CLIENT), 'clientId'));
    }

    #[DataProvider('gesturesWhoseSecretSsmRefuses')]
    public function testAGestureWhoseSecretSsmRefusesIsAServiceUnavailable(string $method, string $uri, string $operation): void
    {
        $this->client->disableReboot();
        $this->loginAnAdministrator();
        self::service(RefusingSsm::class)->refuse($operation);

        $problem = $this->requestJson($method, $uri, 'PATCH' === $method ? ['clientId' => 'client-of-solean', 'clientSecret' => 'secret-of-solean'] : null);

        $this->assertResponseStatusCodeSame(503);
        $this->assertSame(['Service Unavailable', 'secretsUnavailable'], [$problem['title'], $problem['reason']]);
        $this->assertStringNotContainsString('secret-of-solean', (string) $this->client->getResponse()->getContent());
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function gesturesWhoseSecretSsmRefuses(): iterable
    {
        yield 'the client configured, its secret not put' => ['PATCH', self::THE_CLIENT, 'PutParameter'];
        yield 'the client removed, its secret not deleted' => ['DELETE', self::THE_CLIENT, 'DeleteParameter'];
        yield 'an authorization asked, the secret of the client not read' => ['POST', '/api/me/connections/google/authorizations', 'GetParameter'];
    }

    public function testAClientWhoseSecretSsmRefusesToPutKeepsTheFormerOne(): void
    {
        $this->client->disableReboot();
        $this->loginAnAdministrator();
        self::service(RefusingSsm::class)->refuse('PutParameter');

        $this->requestJson('PATCH', self::THE_CLIENT, ['clientId' => 'client-of-solean', 'clientSecret' => 'secret-of-solean']);

        $this->assertSame('client-of-the-double', self::valueAt($this->requestJson('GET', self::THE_CLIENT), 'clientId'));
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
            'gmailNotificationEndpoint' => $this->notificationEndpoint(),
            '_links' => [
                'self' => ['href' => self::THE_CLIENT, 'method' => 'GET'],
                'configure' => ['href' => self::THE_CLIENT, 'method' => 'PATCH'],
            ],
        ], $client);
        $this->assertArrayNotHasKey('connectGoogle', self::recordAt($this->requestJson('GET', '/api/me/connections'), '_links'));
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
    public function testAMemberWhoIsNoAdministratorNeitherSeesNorChangesTheClient(string $member, string $method, string $uri = self::THE_CLIENT): void
    {
        $this->login($member);

        $this->requestJson($method, $uri, match (true) {
            'PATCH' === $method && self::THE_CLIENT === $uri => ['clientId' => 'client-of-solean', 'clientSecret' => 'secret-of-solean'],
            'PATCH' === $method => ['topic' => self::TOPIC, 'pushAccount' => self::PUSH_ACCOUNT],
            default => null,
        });

        $this->assertResponseStatusCodeSame(403);
        $this->loginAnAdministrator();
        $this->assertSame('client-of-the-double', self::valueAt($this->requestJson('GET', self::THE_CLIENT), 'clientId'));
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2?: string}>
     */
    public static function gesturesOfAMemberWhoIsNoAdministrator(): iterable
    {
        foreach (['a prospector with the personal data' => 'prospector-user', 'a prospector without the personal data' => 'assistant-user'] as $who => $member) {
            foreach (['sees' => 'GET', 'configures' => 'PATCH', 'removes' => 'DELETE'] as $gesture => $method) {
                yield sprintf('%s %s', $who, $gesture) => [$member, $method];
            }
            foreach (['names the topic of Gmail' => 'PATCH', 'removes the topic of Gmail' => 'DELETE'] as $gesture => $method) {
                yield sprintf('%s %s', $who, $gesture) => [$member, $method, self::GMAIL_NOTIFICATIONS];
            }
        }
    }

    public function testTheConnectionsLinkTheClientForAnAdministratorOnly(): void
    {
        $this->loginAnAdministrator();
        $this->assertSame(['href' => self::THE_CLIENT, 'method' => 'GET'], self::valueAt($this->requestJson('GET', '/api/me/connections'), '_links', 'googleClient'));

        $this->login('prospector-user');
        $this->assertArrayNotHasKey('googleClient', self::recordAt($this->requestJson('GET', '/api/me/connections'), '_links'));
    }

    public function testAnAnonymousVisitorSeesNoClient(): void
    {
        $this->requestJson('GET', self::THE_CLIENT);

        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * Where the subscription of the account at Google pushes the notifications of Gmail (ADR 0154).
     */
    private function notificationEndpoint(): string
    {
        return sprintf('%s/api/accounts/%s/gmail-notifications', Typed::string($_SERVER['DEFAULT_URI']), $this->accountOfTheFixtures()->value);
    }

    private function loginAnAdministrator(): void
    {
        $this->login('administrator');
    }
}
