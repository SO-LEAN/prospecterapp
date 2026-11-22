<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Correspondent;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Tests\App\Base\ApiTestCase;

/**
 * The day of the signed-in member on their dashboard: what they planned today, then the next five exchanges to come;
 * the clock of the tests says it is 2026-10-15 09:00 UTC, 11:00 in Paris.
 */
#[Group('api')]
class MyDayApiTest extends ApiTestCase
{
    private const string TODAY = '/api/me/day?from=2026-10-15T00:00:00%2B02:00&to=2026-10-16T00:00:00%2B02:00';

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testTheDayShowsWhatIPlannedTodayThenTheNextFiveWithWhoTheyAreWith(): void
    {
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $ada = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'], 'organizationIds' => [$organization]])['id'];
        $this->requestJson('POST', '/api/contacts/'.$ada.'/upcoming-exchanges', ['channel' => 'videoCall', 'occurredAt' => '2026-10-15T14:00:00+00:00']);
        $cancelled = $this->requestJson('POST', '/api/organizations/'.$organization.'/upcoming-exchanges', ['exchange' => ['channel' => 'meeting', 'occurredAt' => '2026-10-15T12:00:00+00:00']])['items'][0]['exchange']['id'];
        $this->requestJson('POST', '/api/exchanges/'.$cancelled.'/cancellation', []);
        foreach (range(16, 21) as $day) {
            $this->requestJson('POST', '/api/organizations/'.$organization.'/upcoming-exchanges', ['exchange' => ['channel' => 'call', 'occurredAt' => sprintf('2026-10-%dT08:00:00+00:00', $day)]]);
        }

        $myDay = $this->requestJson('GET', self::TODAY);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([['Ada Lovelace', 'videoCall', '2026-10-15T14:00:00+00:00']], array_map(self::described(...), $myDay['today']));
        $this->assertSame(array_map(static fn (int $day): array => ['ACME', 'call', sprintf('2026-10-%dT08:00:00+00:00', $day)], range(16, 20)), array_map(self::described(...), $myDay['next']));
    }

    /**
     * The organization is erased at once, its exchanges by a reaction after it: in between, the day is read all the
     * same, without them.
     */
    public function testAnExchangeWithAnOrganizationErasedBeforeItLeavesTheDayReadable(): void
    {
        $ada = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'], 'organizationIds' => [$this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id']]])['id'];
        $this->requestJson('POST', '/api/contacts/'.$ada.'/upcoming-exchanges', ['channel' => 'videoCall', 'occurredAt' => '2026-10-15T14:00:00+00:00']);
        $erased = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'QA rendez-vous']])['id'];
        $this->requestJson('POST', '/api/organizations/'.$erased.'/upcoming-exchanges', ['exchange' => ['channel' => 'meeting', 'occurredAt' => '2026-10-15T16:00:00+00:00']]);
        $this->requestJson('POST', '/api/organizations/'.$erased.'/upcoming-exchanges', ['exchange' => ['channel' => 'call', 'occurredAt' => '2026-10-16T08:00:00+00:00']]);
        $this->eraseBeforeItsExchanges($erased);

        $myDay = $this->requestJson('GET', self::TODAY);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([[['Ada Lovelace', 'videoCall', '2026-10-15T14:00:00+00:00']], []], [array_map(self::described(...), $myDay['today']), $myDay['next']]);
    }

    /**
     * A contact is shown with the organization the exchange is kept for; the organization itself needs none.
     */
    public function testAnExchangeWithAContactShowsTheirOrganization(): void
    {
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $ada = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'], 'organizationIds' => [$organization]])['id'];
        $this->requestJson('POST', '/api/contacts/'.$ada.'/upcoming-exchanges', ['channel' => 'videoCall', 'occurredAt' => '2026-10-15T14:00:00+00:00']);
        $this->requestJson('POST', '/api/organizations/'.$organization.'/upcoming-exchanges', ['exchange' => ['channel' => 'meeting', 'occurredAt' => '2026-10-15T16:00:00+00:00']]);

        $today = $this->requestJson('GET', self::TODAY)['today'];

        $this->assertSame([['id' => $organization, 'name' => 'ACME'], null], array_map(static fn (array $item): ?array => isset($item['organization']) ? ['id' => $item['organization']['id'], 'name' => $item['organization']['name']] : null, $today));
    }

    public function testAnExchangeWhoseTimeHasComeIsMarkedDoneOrMissedFromTheDay(): void
    {
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $passed = $this->plannedBy($this->memberIdOf('prospector-user'), $organization, '2026-10-15T08:00:00+00:00');
        $toCome = $this->requestJson('POST', '/api/organizations/'.$organization.'/upcoming-exchanges', ['exchange' => ['channel' => 'call', 'occurredAt' => '2026-10-15T14:00:00+00:00']])['items'][0]['exchange']['id'];

        $links = array_column(array_column($this->requestJson('GET', self::TODAY)['today'], 'exchange'), '_links', 'id');

        $this->assertSame([
            ['href' => '/api/exchanges/'.$passed.'/done', 'method' => 'POST'],
            ['href' => '/api/exchanges/'.$passed.'/missed', 'method' => 'POST'],
        ], [$links[$passed]['markDone'] ?? null, $links[$passed]['markMissed'] ?? null]);
        $this->assertSame(['self'], array_keys($links[$toCome]));
    }

    public function testTheExchangesOfAnOrganizationNoLongerProspectedLeaveTheDay(): void
    {
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $this->requestJson('POST', '/api/organizations/'.$organization.'/upcoming-exchanges', ['exchange' => ['channel' => 'meeting', 'occurredAt' => '2026-10-15T14:00:00+00:00']]);
        $this->requestJson('POST', '/api/organizations/'.$organization.'/upcoming-exchanges', ['exchange' => ['channel' => 'meeting', 'occurredAt' => '2026-10-16T14:00:00+00:00']]);

        $this->requestJson('DELETE', '/api/organizations/'.$organization);
        $this->assertResponseStatusCodeSame(204);

        $this->assertSame([[], []], array_values(array_intersect_key($this->requestJson('GET', self::TODAY), ['today' => true, 'next' => true])));
    }

    /**
     * Planned while they had the right to manage personal data, the exchange is still theirs to handle.
     */
    public function testAHolderWithoutTheRightToManagePersonalDataReadsAndHandlesTheirDay(): void
    {
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $assistant = $this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector);
        $passed = $this->plannedBy($this->memberIdOf($assistant), $organization, '2026-10-15T08:00:00+00:00');
        $this->login($assistant);

        $myDay = $this->requestJson('GET', self::TODAY);

        $this->assertSame([$passed], array_column(array_column($myDay['today'], 'exchange'), 'id'));
        $this->assertArrayHasKey('markDone', $myDay['today'][0]['exchange']['_links']);
    }

    /**
     * The same prospector or a colleague: every role reads their own day, never that of the others.
     */
    #[DataProvider('colleagues')]
    public function testEachMemberReadsTheirOwnDay(string $reference, Role ...$roles): void
    {
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $this->requestJson('POST', '/api/organizations/'.$organization.'/upcoming-exchanges', ['exchange' => ['channel' => 'meeting', 'occurredAt' => '2026-10-15T14:00:00+00:00']]);
        $this->login($this->aMemberOfTheFixtures($reference, ...$roles));

        $myDay = $this->requestJson('GET', self::TODAY);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([[], []], [$myDay['today'], $myDay['next']]);
    }

    /**
     * @return iterable<string, list<mixed>>
     */
    public static function colleagues(): iterable
    {
        yield 'a prospector without the right to manage personal data' => ['prospector-without-the-right', Role::User, Role::Prospector];
        yield 'an administrator' => ['administrator', Role::Prospector, Role::Admin];
    }

    /**
     * A day with nothing planned proposes to plan, with a contact searched by their name.
     */
    #[DataProvider('planners')]
    public function testAMemberWhoMayPlanIsOfferedTheSearchOfTheContacts(string $reference, Role ...$roles): void
    {
        $this->login($this->aMemberOfTheFixtures($reference, ...$roles));

        $this->assertSame(['href' => '/api/contacts', 'method' => 'GET'], $this->requestJson('GET', self::TODAY)['_links']['searchContacts'] ?? null);
    }

    /**
     * @return iterable<string, list<mixed>>
     */
    public static function planners(): iterable
    {
        yield 'a prospector with the right to manage personal data' => ['prospector-with-the-right', Role::User, Role::Prospector, Role::PersonalData];
        yield 'an administrator' => ['administrator', Role::Prospector, Role::Admin];
    }

    public function testAMemberWithoutTheRightToManagePersonalDataIsOfferedTheSearchOfTheContacts(): void
    {
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $this->assertSame(['href' => '/api/contacts', 'method' => 'GET'], $this->requestJson('GET', self::TODAY)['_links']['searchContacts'] ?? null);
    }

    public function testAMemberWhoIsNotAProspectorHasNoDay(): void
    {
        $this->login($this->aMemberOfTheFixtures('assistant', Role::User));

        $this->requestJson('GET', self::TODAY);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testADayEndsAfterItStarts(): void
    {
        $reversed = $this->requestJson('GET', '/api/me/day?from=2026-10-16T00:00:00%2B02:00&to=2026-10-15T00:00:00%2B02:00');

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['to'], 'calendarPeriodReversed'], [$this->violatedFields($reversed), $reversed['reason']]);
    }

    public function testADayHasItsTwoBounds(): void
    {
        $this->requestJson('GET', '/api/me/day');
        $this->assertResponseStatusCodeSame(422);
        $this->requestJson('GET', '/api/me/day?from=2026-10-15T00:00:00%2B02:00');
        $this->assertResponseStatusCodeSame(422);
    }

    /**
     * Planned the day before by the member, so that its time has come at the clock of the tests.
     */
    private function plannedBy(string $memberId, string $organization, string $at): string
    {
        $facts = ExchangeFacts::planned(Channel::Call, new \DateTimeImmutable($at), Duration::inMinutes(null), null);
        $prospect = self::getContainer()->get(OrganizationRepository::class)->get(new OrganizationId($organization));
        $planned = Exchange::planned(new Gesture($prospect->accountId(), new MemberId($memberId), new \DateTimeImmutable('2026-10-14 09:00 UTC')), new ExchangeId(self::newIdentity()), Correspondent::organization($prospect->id()), $facts);
        self::getContainer()->get(ExchangeRepository::class)->save($planned);

        return $planned->id()->value;
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return list<mixed>
     */
    private static function described(array $item): array
    {
        return [$item['interlocutor']['name'], $item['exchange']['channel'], $item['exchange']['occurredAt']];
    }
}
