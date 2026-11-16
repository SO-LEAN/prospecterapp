<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * The calendar of the signed-in member, a period at a time; the clock of the tests says it is 2026-10-15 09:00 UTC,
 * the demonstration has the prospector note two exchanges with Ada Lovelace, on the 13th and the 14th.
 */
#[Group('api')]
class CalendarApiTest extends ApiTestCase
{
    private const string WEEK_OF_THE_DEMONSTRATION = '/api/me/calendar?from=2026-10-12T00:00:00%2B02:00&to=2026-10-19T00:00:00%2B02:00';
    private const string NEXT_WEEK = '/api/me/calendar?from=2026-10-19T00:00:00%2B02:00&to=2026-10-26T00:00:00%2B01:00';

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testThePeriodShowsTheExchangesINotedPlannedOrCancelledWithWhoTheyAreWith(): void
    {
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $ada = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'], 'organizationIds' => [$organization]])['id'];
        $this->requestJson('POST', '/api/contacts/'.$ada.'/upcoming-exchanges', ['channel' => 'videoCall', 'occurredAt' => '2026-10-20T12:00:00+00:00']);
        $cancelled = $this->requestJson('POST', '/api/organizations/'.$organization.'/upcoming-exchanges', ['exchange' => ['channel' => 'meeting', 'occurredAt' => '2026-10-19T08:00:00+00:00']])['items'][0]['exchange']['id'];
        $this->requestJson('POST', '/api/exchanges/'.$cancelled.'/cancellation', []);
        $this->requestJson('POST', '/api/contacts/'.$ada.'/upcoming-exchanges', ['channel' => 'call', 'occurredAt' => '2026-10-26T08:00:00+00:00']);

        $calendar = $this->requestJson('GET', self::NEXT_WEEK);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([
            ['ACME', 'meeting', '2026-10-19T08:00:00+00:00', 'cancelled', 30],
            ['Ada Lovelace', 'videoCall', '2026-10-20T12:00:00+00:00', 'planned', 30],
        ], array_map(self::described(...), $calendar['items']));
    }

    public function testTheExchangesOfAnOrganizationNoLongerProspectedLeaveTheCalendar(): void
    {
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $ada = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'], 'organizationIds' => [$organization]])['id'];
        $this->requestJson('POST', '/api/contacts/'.$ada.'/upcoming-exchanges', ['channel' => 'videoCall', 'occurredAt' => '2026-10-20T12:00:00+00:00']);
        $this->requestJson('POST', '/api/organizations/'.$organization.'/upcoming-exchanges', ['exchange' => ['channel' => 'meeting', 'occurredAt' => '2026-10-21T08:00:00+00:00']]);

        $this->requestJson('DELETE', '/api/organizations/'.$organization);
        $this->assertResponseStatusCodeSame(204);

        $this->assertSame([], $this->requestJson('GET', self::NEXT_WEEK)['items']);
    }

    /**
     * The organization is erased at once, its exchanges by a reaction after it: in between, the calendar is read all
     * the same, without them.
     */
    public function testAnExchangeWithAnOrganizationErasedBeforeItLeavesTheCalendarReadable(): void
    {
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $ada = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'], 'organizationIds' => [$organization]])['id'];
        $this->requestJson('POST', '/api/contacts/'.$ada.'/upcoming-exchanges', ['channel' => 'videoCall', 'occurredAt' => '2026-10-20T12:00:00+00:00']);
        $erased = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'QA rendez-vous']])['id'];
        $this->requestJson('POST', '/api/organizations/'.$erased.'/upcoming-exchanges', ['exchange' => ['channel' => 'meeting', 'occurredAt' => '2026-10-21T08:00:00+00:00']]);
        $this->eraseBeforeItsExchanges($erased);

        $calendar = $this->requestJson('GET', self::NEXT_WEEK);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([['Ada Lovelace', 'videoCall', '2026-10-20T12:00:00+00:00', 'planned', 30]], array_map(self::described(...), $calendar['items']));
    }

    /**
     * Its holder moves an exchange of their calendar while it is to come; what is done, missed or cancelled stays where
     * it is.
     */
    #[DataProvider('holders')]
    public function testOnlyAnExchangeToComeIsMovedFromTheCalendar(string $reference, Role ...$roles): void
    {
        $this->login($this->aMemberOfTheFixtures($reference, ...$roles));
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $planned = $this->requestJson('POST', '/api/organizations/'.$organization.'/upcoming-exchanges', ['exchange' => ['channel' => 'call', 'occurredAt' => '2026-10-20T12:00:00+00:00']])['items'][0]['exchange']['id'];
        $cancelled = $this->requestJson('POST', '/api/organizations/'.$organization.'/upcoming-exchanges', ['exchange' => ['channel' => 'meeting', 'occurredAt' => '2026-10-19T08:00:00+00:00']])['items'][0]['exchange']['id'];
        $this->requestJson('POST', '/api/exchanges/'.$cancelled.'/cancellation', []);

        $links = $this->linksOfTheExchanges($this->requestJson('GET', self::NEXT_WEEK));

        $this->assertSame(['href' => '/api/exchanges/'.$planned.'/moment', 'method' => 'PATCH'], $links[$planned]['move'] ?? null);
        $this->assertArrayNotHasKey('move', $links[$cancelled]);
    }

    /**
     * @return iterable<string, list<mixed>>
     */
    public static function holders(): iterable
    {
        yield 'a prospector with the right to manage personal data' => ['prospector-with-the-right', Role::User, Role::Prospector, Role::PersonalData];
        yield 'an administrator' => ['administrator', Role::Prospector, Role::Admin];
    }

    /**
     * Planned while they had the right to manage personal data, the exchange is still theirs to move.
     */
    public function testAHolderWithoutTheRightToManagePersonalDataMovesTheirExchangeFromTheCalendar(): void
    {
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $ada = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'], 'organizationIds' => [$organization]])['id'];
        $assistant = $this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector);
        $planned = Exchange::plannedWithAContact($this->accountOfTheFixtures(), new MemberId($this->memberIdOf($assistant)), new ExchangeId(self::newIdentity()), new PersonId($ada), [new OrganizationId($organization)], ExchangeFacts::planned(Channel::Call, new \DateTimeImmutable('2026-10-20T12:00:00+00:00'), Duration::inMinutes(null), null), new \DateTimeImmutable('2026-10-15 09:00 UTC'));
        self::getContainer()->get(ExchangeRepository::class)->save($planned);
        $this->login($assistant);

        $links = $this->linksOfTheExchanges($this->requestJson('GET', self::NEXT_WEEK));

        $this->assertSame(['href' => '/api/exchanges/'.$planned->id()->value.'/moment', 'method' => 'PATCH'], $links[$planned->id()->value]['move'] ?? null);
    }

    public function testADoneExchangeIsNotMovedFromTheCalendar(): void
    {
        $links = $this->linksOfTheExchanges($this->requestJson('GET', self::WEEK_OF_THE_DEMONSTRATION));

        $this->assertSame([[], []], array_values(array_map(static fn (array $links): array => array_diff(array_keys($links), ['self']), $links)));
    }

    /**
     * A free slot plans with a contact searched by text: a member with the right to manage personal data, as planning.
     */
    #[DataProvider('holders')]
    public function testAMemberWhoMayPlanIsOfferedTheSearchOfTheContacts(string $reference, Role ...$roles): void
    {
        $this->login($this->aMemberOfTheFixtures($reference, ...$roles));

        $this->assertSame(['href' => '/api/contacts', 'method' => 'GET'], $this->requestJson('GET', self::NEXT_WEEK)['_links']['searchContacts'] ?? null);
    }

    public function testAMemberWithoutTheRightToManagePersonalDataIsNotOfferedTheSearchOfTheContacts(): void
    {
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $this->assertSame(['self'], array_keys($this->requestJson('GET', self::NEXT_WEEK)['_links']));
    }

    /**
     * @param array{items: list<array{exchange: array{id: string, _links: array<string, mixed>}}>} $calendar
     *
     * @return array<string, array<string, mixed>> the links of each exchange, by its id
     */
    private function linksOfTheExchanges(array $calendar): array
    {
        return array_column(array_column($calendar['items'], 'exchange'), '_links', 'id');
    }

    public function testTheExchangesNotedAreInTheCalendarOfTheirAuthor(): void
    {
        $this->assertSame(['done', 'done'], array_column(array_column($this->requestJson('GET', self::WEEK_OF_THE_DEMONSTRATION)['items'], 'exchange'), 'status'));
    }

    /**
     * The same prospector or a colleague: every role reads their own calendar, never that of the others.
     */
    #[DataProvider('colleagues')]
    public function testEachMemberReadsTheirOwnCalendar(string $reference, Role ...$roles): void
    {
        $this->login($this->aMemberOfTheFixtures($reference, ...$roles));

        $this->assertSame([], $this->requestJson('GET', self::WEEK_OF_THE_DEMONSTRATION)['items']);
        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * @return iterable<string, list<mixed>>
     */
    public static function colleagues(): iterable
    {
        yield 'a prospector without the right to manage personal data' => ['prospector-without-the-right', Role::User, Role::Prospector];
        yield 'an administrator' => ['administrator', Role::Prospector, Role::Admin];
    }

    public function testAMemberWhoIsNotAProspectorHasNoCalendar(): void
    {
        $this->login($this->aMemberOfTheFixtures('assistant', Role::User));

        $this->requestJson('GET', self::WEEK_OF_THE_DEMONSTRATION);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAPeriodLastsSixWeeksAtMost(): void
    {
        $tooLong = $this->requestJson('GET', '/api/me/calendar?from=2026-11-01T00:00:00%2B01:00&to=2027-01-01T00:00:00%2B01:00');

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['to'], 'calendarPeriodTooLong'], [$this->violatedFields($tooLong), $tooLong['reason']]);
    }

    public function testAPeriodEndsAfterItStarts(): void
    {
        $reversed = $this->requestJson('GET', '/api/me/calendar?from=2026-11-08T00:00:00%2B01:00&to=2026-11-01T00:00:00%2B01:00');

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['to'], 'calendarPeriodReversed'], [$this->violatedFields($reversed), $reversed['reason']]);
    }

    public function testAPeriodHasItsTwoBounds(): void
    {
        $this->requestJson('GET', '/api/me/calendar');
        $this->assertResponseStatusCodeSame(422);
        $this->requestJson('GET', '/api/me/calendar?from=2026-11-01T00:00:00%2B01:00');
        $this->assertResponseStatusCodeSame(422);
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return list<mixed>
     */
    private static function described(array $item): array
    {
        return [$item['interlocutor']['name'], $item['exchange']['channel'], $item['exchange']['occurredAt'], $item['exchange']['status'], $item['exchange']['durationInMinutes']];
    }
}
