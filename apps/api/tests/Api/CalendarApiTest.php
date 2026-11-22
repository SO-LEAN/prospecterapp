<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ContactCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\Due;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\Task;
use Prospecter\Core\Prospecting\Domain\Model\ToDo;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\SharedKernel\Domain\Gesture;
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
        $planned = Exchange::planned(new Gesture($this->accountOfTheFixtures(), new MemberId($this->memberIdOf($assistant)), new \DateTimeImmutable('2026-10-15 09:00 UTC')), new ExchangeId(self::newIdentity()), new ContactCorrespondent(new PersonId($ada), [new OrganizationId($organization)]), ExchangeFacts::planned(Channel::Call, new \DateTimeImmutable('2026-10-20T12:00:00+00:00'), Duration::inMinutes(null), null));
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
     * A free slot plans with a contact searched by their name.
     */
    #[DataProvider('holders')]
    public function testAMemberWhoMayPlanIsOfferedTheSearchOfTheContacts(string $reference, Role ...$roles): void
    {
        $this->login($this->aMemberOfTheFixtures($reference, ...$roles));

        $this->assertSame(['href' => '/api/contacts', 'method' => 'GET'], $this->requestJson('GET', self::NEXT_WEEK)['_links']['searchContacts'] ?? null);
    }

    public function testAMemberWithoutTheRightToManagePersonalDataIsOfferedTheSearchOfTheContacts(): void
    {
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $this->assertSame(['href' => '/api/contacts', 'method' => 'GET'], $this->requestJson('GET', self::NEXT_WEEK)['_links']['searchContacts'] ?? null);
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

    /**
     * At the clock of the tests, on the offset of the period, the 14th is gone, the hour of 8:00 of the 15th passed.
     */
    #[DataProvider('readersOfTheToDos')]
    public function testThePeriodShowsMyToDosAtTheirDueAndWhatIsLate(string $reference, Role ...$roles): void
    {
        $this->login($this->aMemberOfTheFixtures($reference, ...$roles));
        $ada = $this->ada();
        $late = $this->noteToDo($ada, 'Envoyer mon CV', '2026-10-14');
        $atEight = $this->noteToDo($ada, 'Appeler la RH', '2026-10-15', '08:00');
        $done = $this->noteToDo($ada, 'Relancer', '2026-10-13');
        $this->requestJson('POST', '/api/to-dos/'.$done.'/done', []);
        $this->noteToDo($ada, 'Rappeler', '2026-10-19');

        $calendar = $this->requestJson('GET', self::WEEK_OF_THE_DEMONSTRATION);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([
            [$done, 'Relancer', '2026-10-13', null, ['firstName' => 'Ada', 'lastName' => 'Lovelace'], true],
            [$late, 'Envoyer mon CV', '2026-10-14', null, ['firstName' => 'Ada', 'lastName' => 'Lovelace'], false],
            [$atEight, 'Appeler la RH', '2026-10-15', '08:00', ['firstName' => 'Ada', 'lastName' => 'Lovelace'], false],
        ], array_map(static fn (array $toDo): array => [$toDo['id'], $toDo['text'], $toDo['dueOn'], $toDo['dueAt'], $toDo['contact']['name'], null !== $toDo['doneAt']], $calendar['toDos']));
        $this->assertSame([$late, $atEight], $calendar['late']);
        $this->assertArrayHasKey('tickOff', $calendar['toDos'][1]['_links']);
        $this->assertArrayHasKey('revise', $calendar['toDos'][1]['_links']);
        $this->assertArrayHasKey('untick', $calendar['toDos'][0]['_links']);
    }

    /**
     * @return iterable<string, list<mixed>>
     */
    public static function readersOfTheToDos(): iterable
    {
        yield 'a prospector with the right to manage personal data' => ['prospector-with-the-right', Role::User, Role::Prospector, Role::PersonalData];
        yield 'an administrator' => ['administrator', Role::Prospector, Role::Admin];
    }

    /**
     * Its text may hold personal data: a to-do noted while they had the right is not shown to them without it.
     */
    public function testAMemberWithoutTheRightToManagePersonalDataSeesNoToDoInTheCalendar(): void
    {
        $assistant = $this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector);
        $toDo = ToDo::noted(new Gesture($this->accountOfTheFixtures(), new MemberId($this->memberIdOf($assistant)), new \DateTimeImmutable('2026-10-13 09:00 UTC')), new ToDoId(self::newIdentity()), new ContactCorrespondent(new PersonId(self::newIdentity()), [new OrganizationId(self::newIdentity())]), new Task('Envoyer mon CV', new Due('2026-10-14')));
        self::getContainer()->get(ToDoRepository::class)->save($toDo);
        $this->login($assistant);

        $calendar = $this->requestJson('GET', self::WEEK_OF_THE_DEMONSTRATION);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([[], []], [$calendar['toDos'], $calendar['late']]);
    }

    /**
     * 9:00 UTC is 2:00 in Los Angeles: a to-do of 8:00 is late on the clock of a reader in Paris, not yet on theirs.
     */
    public function testWhatIsLateIsReadOnTheClockOfItsReader(): void
    {
        $this->noteToDo($this->ada(), 'Appeler la RH', '2026-10-15', '08:00');

        $this->assertCount(1, $this->requestJson('GET', self::WEEK_OF_THE_DEMONSTRATION.'&timeZone=Europe/Paris')['late']);
        $this->assertSame([], $this->requestJson('GET', '/api/me/calendar?from=2026-10-12T00:00:00-07:00&to=2026-10-19T00:00:00-07:00&timeZone=America/Los_Angeles')['late']);
    }

    public function testTheTimeZoneOfTheReaderIsOneOfTheWorld(): void
    {
        $refused = $this->requestJson('GET', self::WEEK_OF_THE_DEMONSTRATION.'&timeZone=Mars/Olympus');

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['timeZone'], $this->violatedFields($refused));
    }

    /**
     * A planned exchange whose slot has ended and not processed is late; one to come is not.
     */
    public function testAnExchangePlannedEndedAndNotProcessedIsLate(): void
    {
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $prospect = self::getContainer()->get(OrganizationRepository::class)->get(new OrganizationId($organization));
        $gesture = new Gesture($prospect->accountId(), new MemberId($this->memberIdOf('prospector-user')), new \DateTimeImmutable('2026-10-13 09:00 UTC'));
        $passed = Exchange::planned($gesture, new ExchangeId(self::newIdentity()), new OrganizationCorrespondent($prospect->id()), ExchangeFacts::planned(Channel::Call, new \DateTimeImmutable('2026-10-14T16:00:00+00:00'), Duration::inMinutes(null), null));
        self::getContainer()->get(ExchangeRepository::class)->save($passed);
        $this->requestJson('POST', '/api/organizations/'.$organization.'/upcoming-exchanges', ['exchange' => ['channel' => 'meeting', 'occurredAt' => '2026-10-16T08:00:00+00:00']]);

        $this->assertSame([$passed->id()->value], $this->requestJson('GET', self::WEEK_OF_THE_DEMONSTRATION)['late']);
    }

    private function ada(): string
    {
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];

        return $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'], 'organizationIds' => [$organization]])['id'];
    }

    private function noteToDo(string $contact, string $text, string $dueOn, ?string $dueAt = null): string
    {
        return $this->requestJson('POST', '/api/contacts/'.$contact.'/to-dos', ['text' => $text, 'dueOn' => $dueOn, 'dueAt' => $dueAt])['id'];
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
