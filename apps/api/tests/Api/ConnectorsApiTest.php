<?php

declare(strict_types=1);

namespace Tests\App\Api;

use App\Shared\CommandBus;
use App\Shared\Typed;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Connectors\Application\Client\ConfigureClient\ConfigureClient;
use Prospecter\Core\Connectors\Application\Client\ConfigureMailboxNotifications\ConfigureMailboxNotifications;
use Prospecter\Core\Connectors\Application\Client\RemoveClient\RemoveClient;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\ReadsResources;
use Tests\App\Double\GoogleDouble;

/**
 * A member connects their Google account through the SPA (ADR 0120): the API answers the address of Google, then
 * exchanges the code Google brought the SPA back with; the connection is checked, reconnected, disconnected, theirs
 * only.
 */
#[Group('api')]
class ConnectorsApiTest extends ApiTestCase
{
    use ReadsResources;

    private const string CODE = 'a-code-of-google';
    private const string TOPIC = 'projects/solean/topics/gmail';
    private const string GMAIL_IMPORT = '/api/me/connections/google/gmail-import';
    private const string GMAIL_IMPORT_AUTHORIZATIONS = '/api/me/connections/google/gmail-import/authorizations';

    protected function setUp(): void
    {
        parent::setUp();
        $this->aMemberOfTheFixtures('client-administrator', Role::User, Role::Admin);
        $this->client->disableReboot();
    }

    public function testTheAuthorizationAsksGoogleALastingAccessToTheCalendarOfProspecter(): void
    {
        $this->login();

        $authorization = $this->requestJson('POST', '/api/me/connections/google/authorizations');

        $this->assertResponseStatusCodeSame(201);
        $this->assertStringStartsWith('https://accounts.google.test/o/oauth2/v2/auth?', self::stringAt($authorization, 'address'));
        $query = $this->addressQueryOf($authorization);
        $this->assertSame([
            'client_id' => 'client-of-the-double',
            'redirect_uri' => 'https://front.test.prospecter.io/settings/connectors/google',
            'response_type' => 'code',
            'scope' => 'openid email https://www.googleapis.com/auth/calendar.app.created',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'code_challenge_method' => 'S256',
        ], array_diff_key($query, ['state' => true, 'code_challenge' => true]));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', Typed::string($query['state']));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', Typed::string($query['code_challenge']));
    }

    #[DataProvider('members')]
    public function testEveryMemberConnectsChecksAndDisconnectsTheirGoogleAccount(string $member, Role ...$roles): void
    {
        $this->login([] === $roles ? $member : $this->aMemberOfTheFixtures($member, ...$roles));

        $connections = $this->connect('michel@exemple.fr', 'a-refresh-token');

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame([['google', 'connected', 'michel@exemple.fr']], $this->seen($connections));
        $this->assertSame(['href' => '/api/me/connections/google', 'method' => 'DELETE'], self::valueAt($connections, 'connections', 0, '_links', 'disconnect'));
        $this->assertSame([['google', 'connected', 'michel@exemple.fr']], $this->seen($this->requestJson('GET', '/api/me/connections')));
        $this->assertSame([['google', 'connected', 'michel@exemple.fr']], $this->seen($this->requestJson('POST', '/api/me/connections/checks')));
        $this->assertSame([], self::valueAt($this->requestJson('DELETE', '/api/me/connections/google'), 'connections'));
        $this->assertResponseIsSuccessful();
    }

    /**
     * @return iterable<string, list<mixed>>
     */
    public static function members(): iterable
    {
        yield 'a prospector with the personal data' => ['prospector-user'];
        yield 'a prospector without the personal data' => ['assistant-user'];
        yield 'an administrator' => ['administrator', Role::User, Role::Admin];
    }

    public function testAConnectionGoogleStillGrantsStaysConnected(): void
    {
        $this->login();
        $this->connect('michel@exemple.fr', 'a-refresh-token');

        $checked = $this->requestJson('POST', '/api/me/connections/checks');

        $this->assertResponseIsSuccessful();
        $this->assertSame([['google', 'connected', 'michel@exemple.fr']], $this->seen($checked));
    }

    public function testATokenGoogleRefusesMakesTheConnectionToReconnect(): void
    {
        $this->login();
        $this->connect('michel@exemple.fr', 'a-refresh-token');
        $this->google()->refusesTheTokens();

        $checked = $this->requestJson('POST', '/api/me/connections/checks');

        $this->assertSame([['google', 'toReconnect', 'michel@exemple.fr']], $this->seen($checked));
        $this->assertSame([['google', 'toReconnect', 'michel@exemple.fr']], $this->seen($this->requestJson('GET', '/api/me/connections')));
    }

    public function testGoogleIsAskedThroughTheClientOfTheAccount(): void
    {
        $this->login();
        $this->connect('michel@exemple.fr', 'a-refresh-token');
        $this->assertSame(['client-of-the-double', 'secret-of-the-double'], $this->google()->clientOfTheLastToken());

        $this->theAdministrator(new ConfigureClient($this->memberIdOf('client-administrator'), 'google', 'client-of-the-double', 'a-new-secret', new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)));
        $checked = $this->requestJson('POST', '/api/me/connections/checks');

        $this->assertSame([['google', 'connected', 'michel@exemple.fr']], $this->seen($checked));
        $this->assertSame(['client-of-the-double', 'a-new-secret'], $this->google()->clientOfTheLastToken());
    }

    public function testTheConnectionsOfAFormerClientAreToReconnect(): void
    {
        $this->login();
        $this->connect('michel@exemple.fr', 'a-refresh-token');

        $this->theAdministrator(new ConfigureClient($this->memberIdOf('client-administrator'), 'google', 'another-client', 'its-secret', new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)));
        $checked = $this->requestJson('POST', '/api/me/connections/checks');

        $this->assertSame([['google', 'toReconnect', 'michel@exemple.fr']], $this->seen($checked));
        $this->assertSame(['client_id' => 'another-client'], array_intersect_key($this->addressQueryOf($this->requestJson('POST', '/api/me/connections/google/authorizations')), ['client_id' => true]));
    }

    public function testConnectingAnotherAccountRevokesTheFormerAccess(): void
    {
        $this->login();
        $this->connect('michel@exemple.fr', 'the-former-token');

        $connections = $this->connect('autre@exemple.fr', 'the-new-token');

        $this->assertSame([['google', 'connected', 'autre@exemple.fr']], $this->seen($connections));
        $this->assertSame(['the-former-token'], $this->google()->revoked());
    }

    public function testReconnectingTheSameAccountKeepsTheAccessGoogleJustGranted(): void
    {
        $this->login();
        $this->connect('michel@exemple.fr', 'the-former-token');

        $this->connect('michel@exemple.fr', 'the-new-token');

        $this->assertSame([], $this->google()->revoked());
        $this->assertSame([['google', 'connected', 'michel@exemple.fr']], $this->seen($this->requestJson('POST', '/api/me/connections/checks')));
    }

    public function testDisconnectingRevokesTheAccessAtGoogle(): void
    {
        $this->login();
        $this->connect('michel@exemple.fr', 'a-refresh-token');

        $connections = $this->requestJson('DELETE', '/api/me/connections/google');

        $this->assertResponseIsSuccessful();
        $this->assertSame([], $connections['connections']);
        $this->assertSame(['a-refresh-token'], $this->google()->revoked());
    }

    public function testWhileGoogleDoesNotAnswerTheCheckIsUnavailableAndTheConnectionKeptAsLastSeen(): void
    {
        $this->login();
        $this->connect('michel@exemple.fr', 'a-refresh-token');
        $this->google()->doesNotAnswer();

        $problem = $this->requestJson('POST', '/api/me/connections/checks');

        $this->assertResponseStatusCodeSame(503);
        $this->assertSame(['Service Unavailable', 'googleUnreachable'], [$problem['title'], $problem['reason']]);
        $this->assertSame([['google', 'connected', 'michel@exemple.fr']], $this->seen($this->requestJson('GET', '/api/me/connections')));
    }

    public function testWhileGoogleLimitsTheRequestsTheCheckIsUnavailableAndTheConnectionKeptAsLastSeen(): void
    {
        $this->login();
        $this->connect('michel@exemple.fr', 'a-refresh-token');
        $this->google()->limits();

        $problem = $this->requestJson('POST', '/api/me/connections/checks');

        $this->assertResponseStatusCodeSame(503);
        $this->assertSame('googleUnreachable', $problem['reason']);
        $this->assertSame([['google', 'connected', 'michel@exemple.fr']], $this->seen($this->requestJson('GET', '/api/me/connections')));
    }

    /**
     * @return iterable<string, array{\Closure(GoogleDouble): void}>
     */
    public static function googleOutOfReach(): iterable
    {
        yield 'Google does not answer' => [static fn (GoogleDouble $google) => $google->doesNotAnswer()];
        yield 'Google fails on its side' => [static fn (GoogleDouble $google) => $google->fails()];
        yield 'Google limits the requests' => [static fn (GoogleDouble $google) => $google->limits()];
    }

    #[DataProvider('googleOutOfReach')]
    public function testWhileGoogleIsOutOfReachTheCodeConnectsNothingAndIsToTryAgain(\Closure $outOfReach): void
    {
        $this->login();
        $state = $this->stateOfANewAuthorization();
        $this->google()->grants(self::CODE, 'michel@exemple.fr', 'a-refresh-token');
        $outOfReach($this->google());

        $problem = $this->requestJson('POST', '/api/me/connections/google', ['code' => self::CODE, 'state' => $state]);

        $this->assertResponseStatusCodeSame(503);
        $this->assertSame('googleUnreachable', $problem['reason']);
        $this->assertSame([], self::valueAt($this->requestJson('GET', '/api/me/connections'), 'connections'));
    }

    public function testWhileGoogleDoesNotAnswerTheMemberDisconnects(): void
    {
        $this->login();
        $this->connect('michel@exemple.fr', 'a-refresh-token');
        $this->google()->doesNotAnswer();

        $connections = $this->requestJson('DELETE', '/api/me/connections/google');

        $this->assertResponseIsSuccessful();
        $this->assertSame([], $connections['connections']);
        $this->assertSame([], self::valueAt($this->requestJson('GET', '/api/me/connections'), 'connections'));
    }

    public function testNoConnectionToDisconnect(): void
    {
        $this->login();

        $problem = $this->requestJson('DELETE', '/api/me/connections/google');

        $this->assertResponseStatusCodeSame(404);
        $this->assertSame('noConnection', $problem['reason']);
    }

    public function testACodeGoogleRefusesConnectsNothing(): void
    {
        $this->login();
        $state = $this->stateOfANewAuthorization();

        $problem = $this->requestJson('POST', '/api/me/connections/google', ['code' => 'a-code-google-never-gave', 'state' => $state]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame('accessNotGranted', $problem['reason']);
        $this->assertSame([], self::valueAt($this->requestJson('GET', '/api/me/connections'), 'connections'));
    }

    public function testAnAccessWithoutTheCalendarConnectsNothing(): void
    {
        $this->login();
        $state = $this->stateOfANewAuthorization();
        $this->google()->grants(self::CODE, 'michel@exemple.fr', 'a-refresh-token', withTheCalendar: false);

        $problem = $this->requestJson('POST', '/api/me/connections/google', ['code' => self::CODE, 'state' => $state]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('incompleteGrant', $problem['reason']);
        $this->assertSame([], self::valueAt($this->requestJson('GET', '/api/me/connections'), 'connections'));
    }

    public function testTheAnswerToTheAuthorizationOfAnotherMemberIsRefused(): void
    {
        $this->login('assistant-user');
        $theirs = $this->stateOfANewAuthorization();

        $this->signInAnotherMember('prospector-user');
        $problem = $this->requestJson('POST', '/api/me/connections/google', ['code' => self::CODE, 'state' => $theirs]);

        $this->assertResponseStatusCodeSame(404);
        $this->assertSame('unknownAuthorization', $problem['reason']);
    }

    public function testMyConnectionIsMineOnly(): void
    {
        $this->login();
        $this->connect('michel@exemple.fr', 'a-refresh-token');

        $this->signInAnotherMember('assistant-user');
        $theirs = $this->requestJson('GET', '/api/me/connections');

        $this->assertResponseIsSuccessful();
        $this->assertSame([], $theirs['connections']);
    }

    public function testTheAnswerOfGoogleNeedsItsCodeAndItsState(): void
    {
        $this->login();

        $problem = $this->requestJson('POST', '/api/me/connections/google', []);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['code', 'state'], $this->violatedFields($problem));
    }

    public function testGoogleIsOfferedWhileItsClientIsConfigured(): void
    {
        $this->login();

        $connections = $this->requestJson('GET', '/api/me/connections');

        $this->assertSame(['href' => '/api/me/connections/google/authorizations', 'method' => 'POST'], self::valueAt($connections, '_links', 'connectGoogle'));
    }

    public function testWithoutAGoogleClientGoogleIsNotOffered(): void
    {
        $this->login();
        $this->withoutAGoogleClient();

        $connections = $this->requestJson('GET', '/api/me/connections');

        $this->assertResponseIsSuccessful();
        $this->assertArrayNotHasKey('connectGoogle', self::recordAt($connections, '_links'));
    }

    public function testWithoutAGoogleClientAnAuthorizationIsRefused(): void
    {
        $this->login();
        $this->withoutAGoogleClient();

        $problem = $this->requestJson('POST', '/api/me/connections/google/authorizations');

        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('providerUnavailable', $problem['reason']);
        $this->assertArrayNotHasKey('address', $problem);
    }

    public function testWithoutAGoogleClientAConnectionIsNeitherReconnectedNorChecked(): void
    {
        $this->login();
        $this->connect('michel@exemple.fr', 'a-refresh-token');

        $this->signInAnotherMember('prospector-user');
        $this->withoutAGoogleClient();
        $connections = $this->requestJson('GET', '/api/me/connections');

        $this->assertSame([['google', 'connected', 'michel@exemple.fr']], $this->seen($connections));
        $this->assertSame(['disconnect'], array_keys(self::recordAt($connections, 'connections', 0, '_links')));
        $this->assertSame([['google', 'connected', 'michel@exemple.fr']], $this->seen($this->requestJson('POST', '/api/me/connections/checks')));
        $this->assertResponseIsSuccessful();
    }

    public function testTheImportOfGmailAsksGoogleTheReadingOfTheMailboxWithTheCalendar(): void
    {
        $this->withATopicOfGmail();
        $this->login();

        $authorization = $this->requestJson('POST', self::GMAIL_IMPORT_AUTHORIZATIONS);

        $this->assertResponseStatusCodeSame(201);
        $query = $this->addressQueryOf($authorization);
        $this->assertSame(['openid email https://www.googleapis.com/auth/calendar.app.created https://www.googleapis.com/auth/gmail.readonly', 'true'], [$query['scope'], $query['include_granted_scopes']]);
    }

    public function testTheImportOfGmailAsksGoogleForTheAccountConnected(): void
    {
        $this->withATopicOfGmail();
        $this->login();
        $this->connect('michel@exemple.fr', 'a-refresh-token');

        $authorization = $this->requestJson('POST', self::GMAIL_IMPORT_AUTHORIZATIONS);

        $this->assertSame('michel@exemple.fr', $this->addressQueryOf($authorization)['login_hint'] ?? null);
    }

    public function testAMemberActivatesTheImportOfGmailThenStopsIt(): void
    {
        $this->withATopicOfGmail();
        $this->login();

        $connections = $this->connectImportingGmail('michel@exemple.fr', 'a-refresh-token');

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame(['active', null], [self::valueAt($connections, 'connections', 0, 'gmailImport'), self::valueAt($connections, 'connections', 0, 'lastGmailNotificationAt')]);
        $this->assertSame(['href' => self::GMAIL_IMPORT, 'method' => 'DELETE'], self::valueAt($connections, 'connections', 0, '_links', 'stopGmailImport'));
        $this->assertSame([self::TOPIC], $this->google()->watches());
        $stopped = $this->requestJson('DELETE', self::GMAIL_IMPORT);
        $this->assertResponseIsSuccessful();
        $this->assertSame([['google', 'connected', 'michel@exemple.fr']], $this->seen($stopped));
        $this->assertSame('off', self::valueAt($stopped, 'connections', 0, 'gmailImport'));
        $this->assertSame(1, $this->google()->stops());
    }

    public function testAMemberRefusingGmailAtGoogleKeepsTheImportOffAndTheCalendarConnected(): void
    {
        $this->withATopicOfGmail();
        $this->login();
        $state = $this->stateOf($this->requestJson('POST', self::GMAIL_IMPORT_AUTHORIZATIONS));
        $this->google()->grants(self::CODE, 'michel@exemple.fr', 'a-refresh-token');

        $connections = $this->requestJson('POST', '/api/me/connections/google', ['code' => self::CODE, 'state' => $state]);

        $this->assertSame([['google', 'connected', 'michel@exemple.fr']], $this->seen($connections));
        $this->assertSame('off', self::valueAt($connections, 'connections', 0, 'gmailImport'));
        $this->assertSame([], $this->google()->watches());
    }

    public function testTheImportOfGmailIsOfferedToAMemberWithThePersonalDataWhileTheAccountHasATopic(): void
    {
        $this->login();
        $this->connect('michel@exemple.fr', 'a-refresh-token');
        $this->assertArrayNotHasKey('importGmail', self::recordAt($this->requestJson('GET', '/api/me/connections'), 'connections', 0, '_links'));

        $this->withATopicOfGmail();

        $this->assertSame(['href' => self::GMAIL_IMPORT_AUTHORIZATIONS, 'method' => 'POST'], self::valueAt($this->requestJson('GET', '/api/me/connections'), 'connections', 0, '_links', 'importGmail'));
    }

    public function testWithoutATopicTheImportOfGmailIsRefused(): void
    {
        $this->login();

        $problem = $this->requestJson('POST', self::GMAIL_IMPORT_AUTHORIZATIONS);

        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('mailImportUnavailable', $problem['reason']);
    }

    public function testAMemberWithoutThePersonalDataIsOfferedNoImportOfGmail(): void
    {
        $this->withATopicOfGmail();
        $this->login('assistant-user');
        $this->connect('michel@exemple.fr', 'a-refresh-token');

        $this->assertArrayNotHasKey('importGmail', self::recordAt($this->requestJson('GET', '/api/me/connections'), 'connections', 0, '_links'));
    }

    /**
     * The administrator of the account removes its client (ADR 0125).
     */
    private function withoutAGoogleClient(): void
    {
        $this->theAdministrator(new RemoveClient($this->memberIdOf('client-administrator'), 'google', new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)));
    }

    /**
     * A gesture of the administrator of the account, arranged through its use case between two requests of the member
     * signed in: its endpoints are those of GoogleClientApiTest.
     */
    private function theAdministrator(object $gesture): void
    {
        self::service(CommandBus::class)->dispatch($gesture);
    }

    /**
     * The client reboots again for another member, as the other tests of the API sign them in: the double of Google
     * is no longer needed then.
     */
    private function signInAnotherMember(string $member): void
    {
        $this->client->enableReboot();
        $this->login($member);
    }

    /**
     * @return array<string, mixed>
     */
    private function connect(string $account, string $refreshToken): array
    {
        $state = $this->stateOfANewAuthorization();
        $this->google()->grants(self::CODE, $account, $refreshToken);

        return $this->requestJson('POST', '/api/me/connections/google', ['code' => self::CODE, 'state' => $state]);
    }

    private function stateOfANewAuthorization(): string
    {
        return $this->stateOf($this->requestJson('POST', '/api/me/connections/google/authorizations'));
    }

    /**
     * @param array<string, mixed> $authorization
     */
    private function stateOf(array $authorization): string
    {
        $query = $this->addressQueryOf($authorization);

        return is_string($query['state'] ?? null) ? $query['state'] : throw new \LogicException('An authorization without state');
    }

    /**
     * @return array<string, mixed>
     */
    private function connectImportingGmail(string $account, string $refreshToken): array
    {
        $state = $this->stateOf($this->requestJson('POST', self::GMAIL_IMPORT_AUTHORIZATIONS));
        $this->google()->grantsGmail(self::CODE, $account, $refreshToken);

        return $this->requestJson('POST', '/api/me/connections/google', ['code' => self::CODE, 'state' => $state]);
    }

    /**
     * The administrator of the account names the topic its mailboxes notify Prospecter through (ADR 0154).
     */
    private function withATopicOfGmail(): void
    {
        $this->theAdministrator(new ConfigureMailboxNotifications($this->memberIdOf('client-administrator'), 'google', self::TOPIC, 'push@solean.iam.gserviceaccount.com', new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)));
    }

    /**
     * @param array<string, mixed> $authorization
     *
     * @return array<int|string, mixed>
     */
    private function addressQueryOf(array $authorization): array
    {
        parse_str((string) parse_url(is_string($authorization['address'] ?? null) ? $authorization['address'] : '', \PHP_URL_QUERY), $query);

        return $query;
    }

    /**
     * @param array<string, mixed> $connections
     *
     * @return list<array{string, string, string}>
     */
    private function seen(array $connections): array
    {
        return array_map(static fn (array $connection): array => [self::stringAt($connection, 'provider'), self::stringAt($connection, 'state'), self::stringAt($connection, 'account')], self::recordsAt($connections, 'connections'));
    }

    private function google(): GoogleDouble
    {
        return self::service(GoogleDouble::class);
    }
}
