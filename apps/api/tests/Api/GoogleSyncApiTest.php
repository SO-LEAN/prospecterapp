<?php

declare(strict_types=1);

namespace Tests\App\Api;

use App\Shared\Typed;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Connectors\Domain\Model\CalendarEntry;
use Prospecter\Core\Connectors\Domain\Model\Channel as ConnectorsChannel;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId as ConnectorsExchangeId;
use Prospecter\Core\Connectors\Domain\Model\ExchangeStatus as ConnectorsExchangeStatus;
use Prospecter\Core\Connectors\Domain\Model\HeldExchange;
use Prospecter\Core\Connectors\Domain\Model\OrganizationId as ConnectorsOrganizationId;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Repository\CalendarEntryRepository;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\Gesture\Gesture;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\ReadsResources;
use Tests\App\Double\GoogleDouble;

/**
 * Whether each exchange a member planned is in their calendar « Prospecter » at Google, a push refused pushed again,
 * one or every one, and the exchanges to come pushed at the connection (ADR 0133), the reactions run within the
 * gesture by the delivery `sync` of the tests; the clock of the tests says it is 2026-10-15 09:00 UTC.
 */
#[Group('api')]
class GoogleSyncApiTest extends ApiTestCase
{
    use ReadsResources;

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
        $this->assertSame(['provider' => 'google', 'state' => 'synced', 'at' => '2026-10-15T09:00:00+00:00', 'reason' => null], $sync['sync']);
        $this->assertSame(['self'], array_keys(self::recordAt($sync, '_links')));
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

    #[DataProvider('prospectors')]
    public function testEveryProspectorPushesAgainTheirExchangeGoogleRefused(string $member, Role ...$roles): void
    {
        $this->login([] === $roles ? $member : $this->aMemberOfTheFixtures($member, ...$roles));
        $this->connectGoogle();
        $this->google()->refusesTheEvents();
        $planned = $this->plan(self::NEXT_TUESDAY);
        $this->google()->acceptsTheEvents();

        $sync = $this->requestJson('POST', '/api/exchanges/'.$planned.'/google-sync/retries');

        $this->assertResponseIsSuccessful();
        $this->assertSame('synced', self::valueAt($sync, 'sync', 'state'));
    }

    #[DataProvider('members')]
    public function testEveryMemberPushesAgainTheirExchangesGoogleRefused(string $member, Role ...$roles): void
    {
        $this->login([] === $roles ? $member : $this->aMemberOfTheFixtures($member, ...$roles));
        $this->connectGoogle();

        $google = self::recordAt($this->requestJson('POST', '/api/me/connections/google/retries'), 'connections', 0);

        $this->assertResponseIsSuccessful();
        $this->assertSame(['synced' => 0, 'failed' => 0], ['synced' => $google['synced'], 'failed' => $google['failed']]);
    }

    /**
     * @return iterable<string, list<mixed>>
     */
    public static function members(): iterable
    {
        yield from self::prospectors();
        yield 'an administrator not prospecting' => ['administrator', Role::User, Role::Admin];
    }

    public function testAMemberNotProspectingNeitherReadsNorPushesAgainTheStateOfAnExchange(): void
    {
        $this->login();
        $planned = $this->plan(self::NEXT_TUESDAY);
        $this->signInAnotherMember($this->aMemberOfTheFixtures('not-prospecting', Role::User));

        $this->requestJson('GET', '/api/exchanges/'.$planned.'/google-sync');
        $this->assertResponseStatusCodeSame(403);

        $this->requestJson('POST', '/api/exchanges/'.$planned.'/google-sync/retries');
        $this->assertResponseStatusCodeSame(403);
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
        $this->assertSame(['href' => '/api/exchanges/'.$planned.'/google-sync/retries', 'method' => 'POST'], self::valueAt($sync, '_links', 'retry'));

        $this->google()->acceptsTheEvents();
        $sync = $this->requestJson('POST', '/api/exchanges/'.$planned.'/google-sync/retries');

        $this->assertResponseIsSuccessful();
        $this->assertSame('synced', self::valueAt($sync, 'sync', 'state'));
        $this->assertCount(1, $this->google()->events());
    }

    public function testMyConnectionCountsMyExchangesToComeSyncedAndFailedAndPushesTheFailedAgain(): void
    {
        $this->login();
        $this->connectGoogle();
        $this->anEntryOfMinePushedOn('2026-10-14 14:00 UTC');
        $this->plan(self::NEXT_TUESDAY);
        $this->google()->refusesTheEvents();
        $this->plan(self::NEXT_WEDNESDAY);

        $google = self::recordAt($this->requestJson('GET', '/api/me/connections'), 'connections', 0);

        $this->assertSame(['synced' => 1, 'failed' => 1], ['synced' => $google['synced'], 'failed' => $google['failed']]);
        $this->assertSame(['href' => '/api/me/connections/google/retries', 'method' => 'POST'], self::valueAt($google, '_links', 'retryFailed'));

        $this->google()->acceptsTheEvents();
        $google = self::recordAt($this->requestJson('POST', '/api/me/connections/google/retries'), 'connections', 0);

        $this->assertResponseIsSuccessful();
        $this->assertSame(['synced' => 2, 'failed' => 0], ['synced' => $google['synced'], 'failed' => $google['failed']]);
        $this->assertArrayNotHasKey('retryFailed', self::recordAt($google, '_links'));
        $this->assertCount(2, $this->google()->events());
    }

    public function testConnectingPushesTheExchangesToCome(): void
    {
        $this->login();
        $planned = $this->plan(self::NEXT_TUESDAY);
        $this->anExchangeOfMinePlannedOn('2026-10-14 14:00 UTC');
        $this->assertSame([], $this->google()->events());

        $this->connectGoogle();

        $this->assertSame(['2026-10-20T16:00:00+02:00'], array_values(array_map(static fn (array $event): string => self::stringAt($event, 'start', 'dateTime'), $this->google()->events())));
        $this->assertSame('synced', self::valueAt($this->requestJson('GET', '/api/exchanges/'.$planned.'/google-sync'), 'sync', 'state'));
    }

    public function testAnExchangePlannedWhileTheConnectionIsToReconnectIsPending(): void
    {
        $this->login();
        $this->connectGoogle();
        $this->google()->refusesTheTokens();

        $planned = $this->plan(self::NEXT_TUESDAY);

        $this->assertSame(['provider' => 'google', 'state' => 'pending', 'at' => null, 'reason' => 'toReconnect'], $this->requestJson('GET', '/api/exchanges/'.$planned.'/google-sync')['sync']);
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
        $this->signInAnotherMember('assistant-user');
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

    /**
     * Another member signs in on a kernel booted again: the services of the former, kept between the requests of a
     * client that does not reboot, are left behind.
     */
    private function signInAnotherMember(string $reference): void
    {
        $kernel = $this->client->getKernel();
        $kernel->shutdown();
        $kernel->boot();
        $this->login($reference);
    }

    private function connectGoogle(): void
    {
        parse_str((string) parse_url(self::stringAt($this->requestJson('POST', '/api/me/connections/google/authorizations'), 'address'), \PHP_URL_QUERY), $query);
        $state = Typed::string($query['state']);
        $this->google()->grants(self::CODE, 'michel@exemple.fr', 'a-refresh-token-'.$state);
        $this->requestJson('POST', '/api/me/connections/google', ['code' => self::CODE, 'state' => $state]);
        $this->assertResponseStatusCodeSame(201);
    }

    private function plan(string $occurredAt): string
    {
        $organization = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']]), 'id');
        $exchange = $this->requestJson('POST', '/api/organizations/'.$organization.'/upcoming-exchanges', ['exchange' => ['channel' => 'call', 'occurredAt' => $occurredAt]]);
        $this->assertResponseStatusCodeSame(201);

        return self::stringAt($exchange, 'items', 0, 'exchange', 'id');
    }

    /**
     * Planned the day before the clock of the tests, as the API refuses to plan in the past.
     */
    private function anExchangeOfMinePlannedOn(string $moment): void
    {
        $organization = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Globex']]), 'id');
        $facts = ExchangeFacts::planned(Channel::Call, new \DateTimeImmutable($moment), Duration::inMinutes(null), null);
        $prospect = self::service(OrganizationRepository::class)->get(new OrganizationId($organization));
        $gesture = new Gesture($prospect->accountId(), new MemberId($this->memberIdOf('prospector-user')), new \DateTimeImmutable('2026-10-13 09:00 UTC'));
        $planned = Exchange::planned($gesture, new ExchangeId(self::newIdentity()), new OrganizationCorrespondent($prospect->id()), $facts);
        self::service(ExchangeRepository::class)->save($planned);
    }

    private function anEntryOfMinePushedOn(string $moment): void
    {
        $exchange = new HeldExchange(new ConnectorsExchangeId(self::newIdentity()), $this->accountOfTheFixtures(), new MemberId($this->memberIdOf('prospector-user')), ConnectorsExchangeStatus::Done, ConnectorsChannel::Call, new \DateTimeImmutable($moment), 30, null, null, new ConnectorsOrganizationId(self::newIdentity()), 'Globex');
        $entry = CalendarEntry::of($exchange, Provider::Google, new \DateTimeImmutable($moment));
        $entry->pushed($exchange, new \DateTimeImmutable($moment));
        self::service(CalendarEntryRepository::class)->save($entry);
    }

    private function google(): GoogleDouble
    {
        return self::service(GoogleDouble::class);
    }
}
