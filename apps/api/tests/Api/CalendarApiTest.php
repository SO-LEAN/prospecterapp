<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
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
