<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\GoogleDouble;

/**
 * A member connects their Google account through the SPA (ADR 0120): the API answers the address of Google, then
 * exchanges the code Google brought the SPA back with; the connection is checked, reconnected, disconnected, theirs
 * only.
 */
#[Group('api')]
class ConnectorsApiTest extends ApiTestCase
{
    private const string CODE = 'a-code-of-google';
    private const array GOOGLE_CLIENT = ['GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_SECRET'];

    /** @var array<string, string|null> the variables of the Google client a test emptied, by name */
    private array $formerVariables = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->disableReboot();
    }

    public function testTheAuthorizationAsksGoogleALastingAccessToTheCalendarOfProspecter(): void
    {
        $this->login();

        $authorization = $this->requestJson('POST', '/api/me/connections/google/authorizations');

        $this->assertResponseStatusCodeSame(201);
        $this->assertStringStartsWith('https://accounts.google.test/o/oauth2/v2/auth?', $authorization['address']);
        parse_str((string) parse_url($authorization['address'], \PHP_URL_QUERY), $query);
        $this->assertSame([
            'client_id' => 'client-of-the-double',
            'redirect_uri' => 'https://front.test.prospecter.io/settings/connectors/google',
            'response_type' => 'code',
            'scope' => 'openid email https://www.googleapis.com/auth/calendar.app.created',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'code_challenge_method' => 'S256',
        ], array_diff_key($query, ['state' => true, 'code_challenge' => true]));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $query['state']);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $query['code_challenge']);
    }

    #[DataProvider('members')]
    public function testEveryMemberConnectsChecksAndDisconnectsTheirGoogleAccount(string $member, Role ...$roles): void
    {
        $this->login([] === $roles ? $member : $this->aMemberOfTheFixtures($member, ...$roles));

        $connections = $this->connect('michel@exemple.fr', 'a-refresh-token');

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame([['google', 'connected', 'michel@exemple.fr']], $this->seen($connections));
        $this->assertSame(['href' => '/api/me/connections/google', 'method' => 'DELETE'], $connections['connections'][0]['_links']['disconnect']);
        $this->assertSame([['google', 'connected', 'michel@exemple.fr']], $this->seen($this->requestJson('GET', '/api/me/connections')));
        $this->assertSame([['google', 'connected', 'michel@exemple.fr']], $this->seen($this->requestJson('POST', '/api/me/connections/checks')));
        $this->assertSame([], $this->requestJson('DELETE', '/api/me/connections/google')['connections']);
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
        $this->assertSame([], $this->requestJson('GET', '/api/me/connections')['connections']);
    }

    public function testAnAccessWithoutTheCalendarConnectsNothing(): void
    {
        $this->login();
        $state = $this->stateOfANewAuthorization();
        $this->google()->grants(self::CODE, 'michel@exemple.fr', 'a-refresh-token', withTheCalendar: false);

        $problem = $this->requestJson('POST', '/api/me/connections/google', ['code' => self::CODE, 'state' => $state]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('incompleteGrant', $problem['reason']);
        $this->assertSame([], $this->requestJson('GET', '/api/me/connections')['connections']);
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

        $this->assertSame(['href' => '/api/me/connections/google/authorizations', 'method' => 'POST'], $connections['_links']['connectGoogle']);
    }

    public function testWithoutAGoogleClientGoogleIsNotOffered(): void
    {
        $this->login();
        $this->withoutAGoogleClient();

        $connections = $this->requestJson('GET', '/api/me/connections');

        $this->assertResponseIsSuccessful();
        $this->assertArrayNotHasKey('connectGoogle', $connections['_links']);
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

    public function testWithoutAGoogleClientAConnectionIsNoLongerReconnected(): void
    {
        $this->login();
        $this->connect('michel@exemple.fr', 'a-refresh-token');

        $this->signInAnotherMember('prospector-user');
        $this->withoutAGoogleClient();
        $connections = $this->requestJson('GET', '/api/me/connections');

        $this->assertSame([['google', 'connected', 'michel@exemple.fr']], $this->seen($connections));
        $this->assertSame(['disconnect'], array_keys($connections['connections'][0]['_links']));
    }

    public function testAnAnonymousVisitorConnectsNothing(): void
    {
        $this->requestJson('POST', '/api/me/connections/google/authorizations');

        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * As an instance installed without its Google client (ADR 0124): the variables are read as the client of Google
     * is first built, the next request of a client that reboots.
     */
    private function withoutAGoogleClient(): void
    {
        foreach (self::GOOGLE_CLIENT as $variable) {
            $this->formerVariables[$variable] = $_SERVER[$variable] ?? null;
            $_SERVER[$variable] = $_ENV[$variable] = '';
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->formerVariables as $variable => $value) {
            $_SERVER[$variable] = $_ENV[$variable] = $value;
        }
        parent::tearDown();
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
        parse_str((string) parse_url($this->requestJson('POST', '/api/me/connections/google/authorizations')['address'], \PHP_URL_QUERY), $query);

        return is_string($query['state'] ?? null) ? $query['state'] : throw new \LogicException('An authorization without state');
    }

    /**
     * @param array<string, mixed> $connections
     *
     * @return list<array{string, string, string}>
     */
    private function seen(array $connections): array
    {
        return array_map(static fn (array $connection): array => [$connection['provider'], $connection['state'], $connection['account']], $connections['connections']);
    }

    private function google(): GoogleDouble
    {
        return self::getContainer()->get(GoogleDouble::class);
    }
}
