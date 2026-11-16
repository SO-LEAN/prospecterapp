<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\GoogleDouble;

/**
 * Whether each exchange a member planned is in their calendar « Prospecter » at Google, a push refused pushed again,
 * one or every one, and the exchanges to come pushed at the connection (ADR 0133), the reactions run within the
 * gesture by the delivery `sync` of the tests; the clock of the tests says it is 2026-10-15 09:00 UTC.
 */
#[Group('api')]
class GoogleSyncApiTest extends ApiTestCase
{
    private const string NEXT_TUESDAY = '2026-10-20T14:00:00+00:00';
    private const string NEXT_WEDNESDAY = '2026-10-21T07:00:00+00:00';
    private const string CODE = 'a-code-of-google';

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->disableReboot();
    }

    #[DataProvider('prospectors')]
    public function testEveryProspectorSeesTheirExchangeSynced(string $member, Role ...$roles): void
    {
        $this->login([] === $roles ? $member : $this->aMemberOfTheFixtures($member, ...$roles));
        $this->connectGoogle();

        $planned = $this->plan(self::NEXT_TUESDAY);
        $sync = $this->requestJson('GET', '/api/exchanges/'.$planned.'/google-sync');

        $this->assertResponseIsSuccessful();
        $this->assertSame(['provider' => 'google', 'state' => 'synced', 'at' => '2026-10-15T09:00:00+00:00'], $sync['sync']);
        $this->assertSame(['self'], array_keys($sync['_links']));
    }

    /**
     * @return iterable<string, list<mixed>>
     */
    public static function prospectors(): iterable
    {
        yield 'a prospector with the personal data' => ['prospector-user'];
        yield 'a prospector without the personal data' => ['assistant-user'];
        yield 'an administrator prospecting' => ['administrator', Role::User, Role::Prospector, Role::Admin];
    }

    public function testAPushGoogleRefusesIsFailedThenPushedAgainByItsHolder(): void
    {
        $this->login();
        $this->connectGoogle();
        $this->google()->refusesTheEvents();

        $planned = $this->plan(self::NEXT_TUESDAY);

        $this->assertResponseStatusCodeSame(201);
        $sync = $this->requestJson('GET', '/api/exchanges/'.$planned.'/google-sync');
        $this->assertSame(['provider' => 'google', 'state' => 'failed', 'at' => '2026-10-15T09:00:00+00:00', 'reason' => 'calendarRefusedThePush'], $sync['sync']);
        $this->assertSame(['href' => '/api/exchanges/'.$planned.'/google-sync/retries', 'method' => 'POST'], $sync['_links']['retry']);

        $this->google()->acceptsTheEvents();
        $sync = $this->requestJson('POST', '/api/exchanges/'.$planned.'/google-sync/retries');

        $this->assertResponseIsSuccessful();
        $this->assertSame('synced', $sync['sync']['state']);
        $this->assertCount(1, $this->google()->events());
    }

    public function testMyConnectionCountsMyExchangesToComeSyncedAndFailedAndPushesTheFailedAgain(): void
    {
        $this->login();
        $this->connectGoogle();
        $this->plan(self::NEXT_TUESDAY);
        $this->google()->refusesTheEvents();
        $this->plan(self::NEXT_WEDNESDAY);

        $google = $this->requestJson('GET', '/api/me/connections')['connections'][0];

        $this->assertSame(['synced' => 1, 'failed' => 1], ['synced' => $google['synced'], 'failed' => $google['failed']]);
        $this->assertSame(['href' => '/api/me/connections/google/retries', 'method' => 'POST'], $google['_links']['retryFailed']);

        $this->google()->acceptsTheEvents();
        $google = $this->requestJson('POST', '/api/me/connections/google/retries')['connections'][0];

        $this->assertResponseIsSuccessful();
        $this->assertSame(['synced' => 2, 'failed' => 0], ['synced' => $google['synced'], 'failed' => $google['failed']]);
        $this->assertArrayNotHasKey('retryFailed', $google['_links']);
        $this->assertCount(2, $this->google()->events());
    }

    public function testConnectingPushesTheExchangesToCome(): void
    {
        $this->login();
        $planned = $this->plan(self::NEXT_TUESDAY);
        $this->assertSame([], $this->google()->events());

        $this->connectGoogle();

        $this->assertCount(1, $this->google()->events());
        $this->assertSame('synced', $this->requestJson('GET', '/api/exchanges/'.$planned.'/google-sync')['sync']['state']);
    }

    public function testAnExchangePlannedWhileTheConnectionIsToReconnectIsPending(): void
    {
        $this->login();
        $this->connectGoogle();
        $this->google()->refusesTheTokens();

        $planned = $this->plan(self::NEXT_TUESDAY);

        $this->assertSame(['provider' => 'google', 'state' => 'pending', 'reason' => 'toReconnect'], $this->requestJson('GET', '/api/exchanges/'.$planned.'/google-sync')['sync']);
    }

    public function testWithoutAConnectionAnExchangeHasNoSync(): void
    {
        $this->login();

        $planned = $this->plan(self::NEXT_TUESDAY);

        $this->assertNull($this->requestJson('GET', '/api/exchanges/'.$planned.'/google-sync')['sync']);
        $this->assertResponseIsSuccessful();
    }

    public function testTheExchangeOfAColleagueHasNoSyncForMeAndIDoNotPushItAgain(): void
    {
        $this->login();
        $this->connectGoogle();
        $this->google()->refusesTheEvents();
        $planned = $this->plan(self::NEXT_TUESDAY);
        $this->login($this->aMemberOfTheFixtures('colleague', Role::User, Role::Prospector, Role::PersonalData));
        $this->connectGoogle();

        $this->assertNull($this->requestJson('GET', '/api/exchanges/'.$planned.'/google-sync')['sync']);

        $refusal = $this->requestJson('POST', '/api/exchanges/'.$planned.'/google-sync/retries');

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame('notTheHolder', $refusal['reason']);
    }

    public function testTheSyncOfAnExchangeUnknownInMyAccountIsNotFound(): void
    {
        $this->login();
        $unknown = self::newIdentity();

        $this->requestJson('GET', '/api/exchanges/'.$unknown.'/google-sync');
        $this->assertResponseStatusCodeSame(404);

        $this->requestJson('POST', '/api/exchanges/'.$unknown.'/google-sync/retries');
        $this->assertResponseStatusCodeSame(404);
    }

    private function connectGoogle(): void
    {
        parse_str((string) parse_url($this->requestJson('POST', '/api/me/connections/google/authorizations')['address'], \PHP_URL_QUERY), $query);
        $this->google()->grants(self::CODE, 'michel@exemple.fr', 'a-refresh-token-'.$query['state']);
        $this->requestJson('POST', '/api/me/connections/google', ['code' => self::CODE, 'state' => $query['state']]);
        $this->assertResponseStatusCodeSame(201);
    }

    private function plan(string $occurredAt): string
    {
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $exchange = $this->requestJson('POST', '/api/organizations/'.$organization.'/upcoming-exchanges', ['exchange' => ['channel' => 'call', 'occurredAt' => $occurredAt]]);
        $this->assertResponseStatusCodeSame(201);

        return $exchange['items'][0]['exchange']['id'];
    }

    private function google(): GoogleDouble
    {
        return self::getContainer()->get(GoogleDouble::class);
    }
}
