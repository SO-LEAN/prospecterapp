<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
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
use Tests\App\Base\ApiTestCase;

/**
 * The exchanges the signed-in member planned whose end has passed and that they have not handled yet, on their
 * dashboard; the clock of the tests says it is 2026-10-15 09:00 UTC.
 */
#[Group('api')]
class ExchangesToProcessApiTest extends ApiTestCase
{
    private const string TO_PROCESS = '/api/me/exchanges-to-process';

    private string $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
        $this->organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
    }

    public function testTheExchangesIPlannedWhoseEndHasPassedAreToProcessTheOldestFirst(): void
    {
        $me = $this->memberIdOf('prospector-user');
        $yesterday = $this->plannedBy($me, '2026-10-14T16:00:00+00:00');
        $thisMorning = $this->plannedBy($me, '2026-10-15T08:00:00+00:00');
        $this->plannedBy($me, '2026-10-15T08:45:00+00:00');
        $this->requestJson('POST', '/api/organizations/'.$this->organization.'/upcoming-exchanges', ['exchange' => ['channel' => 'meeting', 'occurredAt' => '2026-10-15T14:00:00+00:00']]);

        $toProcess = $this->requestJson('GET', self::TO_PROCESS);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([[$yesterday, 'ACME'], [$thisMorning, 'ACME']], array_map(static fn (array $item): array => [$item['exchange']['id'], $item['interlocutor']['name']], $toProcess['items']));
        $this->assertSame(['href' => self::TO_PROCESS, 'method' => 'GET'], $toProcess['_links']['self']);
    }

    /**
     * The organization is erased at once, its exchanges by a reaction after it: in between, the exchanges to process
     * are read all the same, without them.
     */
    public function testAnExchangeWithAnOrganizationErasedBeforeItIsNoLongerToProcess(): void
    {
        $this->plannedBy($this->memberIdOf('prospector-user'), '2026-10-14T16:00:00+00:00');
        $this->eraseBeforeItsExchanges($this->organization);

        $this->assertSame([], $this->requestJson('GET', self::TO_PROCESS)['items']);
        $this->assertResponseStatusCodeSame(200);
    }

    public function testAnExchangeToProcessIsMarkedDoneOrMissedCancelledOrMovedFromTheList(): void
    {
        $passed = $this->plannedBy($this->memberIdOf('prospector-user'), '2026-10-15T08:00:00+00:00');

        $links = $this->requestJson('GET', self::TO_PROCESS)['items'][0]['exchange']['_links'];

        $this->assertSame([
            'move' => ['href' => '/api/exchanges/'.$passed.'/moment', 'method' => 'PATCH'],
            'markDone' => ['href' => '/api/exchanges/'.$passed.'/done', 'method' => 'POST'],
            'markMissed' => ['href' => '/api/exchanges/'.$passed.'/missed', 'method' => 'POST'],
            'cancel' => ['href' => '/api/exchanges/'.$passed.'/cancellation', 'method' => 'POST'],
        ], array_intersect_key($links, ['move' => true, 'markDone' => true, 'markMissed' => true, 'cancel' => true]));
    }

    public function testAnExchangeHandledIsNoLongerToProcess(): void
    {
        $done = $this->plannedBy($this->memberIdOf('prospector-user'), '2026-10-15T07:00:00+00:00');
        $missed = $this->plannedBy($this->memberIdOf('prospector-user'), '2026-10-15T08:00:00+00:00');

        $this->requestJson('POST', '/api/exchanges/'.$done.'/done', ['text' => 'Mission de 3 mois']);
        $this->requestJson('POST', '/api/exchanges/'.$missed.'/missed', []);

        $this->assertSame([], $this->requestJson('GET', self::TO_PROCESS)['items']);
    }

    /**
     * Planned while they had the right to manage personal data, the exchange is still theirs to handle.
     */
    public function testAHolderWithoutTheRightToManagePersonalDataProcessesTheirExchanges(): void
    {
        $assistant = $this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector);
        $passed = $this->plannedBy($this->memberIdOf($assistant), '2026-10-15T08:00:00+00:00');
        $this->login($assistant);

        $items = $this->requestJson('GET', self::TO_PROCESS)['items'];

        $this->assertSame([$passed], array_column(array_column($items, 'exchange'), 'id'));
        $this->assertArrayHasKey('markDone', $items[0]['exchange']['_links']);
    }

    /**
     * Every role reads the exchanges they planned, never those of the others.
     */
    #[DataProvider('colleagues')]
    public function testEachMemberProcessesOnlyTheirOwnExchanges(string $reference, Role ...$roles): void
    {
        $this->plannedBy($this->memberIdOf('prospector-user'), '2026-10-15T08:00:00+00:00');
        $this->login($this->aMemberOfTheFixtures($reference, ...$roles));

        $this->assertSame([], $this->requestJson('GET', self::TO_PROCESS)['items']);
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

    /**
     * At the clock of the tests, 9:00 UTC on the 15th: the to-dos of the 15th, whatever their hour, and those before it.
     */
    #[DataProvider('readersOfTheToDos')]
    public function testMyToDosOfTheDayAndLateAreToProcessTheMostOverdueFirst(string $reference, Role ...$roles): void
    {
        $this->login($this->aMemberOfTheFixtures($reference, ...$roles));
        $ada = $this->ada();
        $today = $this->noteToDo($ada, 'Appeler la RH', '2026-10-15', '17:00');
        $late = $this->noteToDo($ada, 'Envoyer mon CV', '2026-10-13');
        $done = $this->noteToDo($ada, 'Relancer', '2026-10-14');
        $this->requestJson('POST', '/api/to-dos/'.$done.'/done', []);
        $this->noteToDo($ada, 'Rappeler', '2026-10-16');

        $toDos = $this->requestJson('GET', self::TO_PROCESS)['toDos'];

        $this->assertSame([[$late, 'Envoyer mon CV', 2], [$today, 'Appeler la RH', 0]], array_map(static fn (array $item): array => [$item['toDo']['id'], $item['toDo']['text'], $item['daysOverdue']], $toDos));
        $this->assertArrayHasKey('tickOff', $toDos[0]['toDo']['_links']);
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
    public function testAMemberWithoutTheRightToManagePersonalDataHasNoToDoToProcess(): void
    {
        $assistant = $this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector);
        $toDo = ToDo::noted(new Gesture($this->accountOfTheFixtures(), new MemberId($this->memberIdOf($assistant)), new \DateTimeImmutable('2026-10-13 09:00 UTC')), new ToDoId(self::newIdentity()), new OrganizationCorrespondent(new OrganizationId($this->organization)), new Task('Envoyer mon CV', new Due('2026-10-14')));
        self::getContainer()->get(ToDoRepository::class)->save($toDo);
        $this->login($assistant);

        $this->assertSame([], $this->requestJson('GET', self::TO_PROCESS)['toDos']);
        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * 9:00 UTC on the 15th is still the 14th in Honolulu.
     */
    public function testTheDayIsThatOfTheClockOfItsReader(): void
    {
        $this->noteToDo($this->ada(), 'Envoyer mon CV', '2026-10-15');

        $this->assertCount(1, $this->requestJson('GET', self::TO_PROCESS.'?timeZone=Europe/Paris')['toDos']);
        $this->assertSame([], $this->requestJson('GET', self::TO_PROCESS.'?timeZone=Pacific/Honolulu')['toDos']);
    }

    public function testTheTimeZoneOfTheReaderIsOneOfTheWorld(): void
    {
        $refused = $this->requestJson('GET', self::TO_PROCESS.'?timeZone=Mars/Olympus');

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['timeZone'], $this->violatedFields($refused));
    }

    private function ada(): string
    {
        return $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'], 'organizationIds' => [$this->organization]])['id'];
    }

    private function noteToDo(string $contact, string $text, string $dueOn, ?string $dueAt = null): string
    {
        return $this->requestJson('POST', '/api/contacts/'.$contact.'/to-dos', ['text' => $text, 'dueOn' => $dueOn, 'dueAt' => $dueAt])['id'];
    }

    public function testAMemberWhoIsNotAProspectorHasNoExchangeToProcess(): void
    {
        $this->login($this->aMemberOfTheFixtures('assistant', Role::User));

        $this->requestJson('GET', self::TO_PROCESS);

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * Planned the day before by the member, half an hour long, so that its time has come at the clock of the tests.
     */
    private function plannedBy(string $memberId, string $at): string
    {
        $facts = ExchangeFacts::planned(Channel::Call, new \DateTimeImmutable($at), Duration::inMinutes(null), null);
        $prospect = self::getContainer()->get(OrganizationRepository::class)->get(new OrganizationId($this->organization));
        $planned = Exchange::planned(new Gesture($prospect->accountId(), new MemberId($memberId), new \DateTimeImmutable('2026-10-13 09:00 UTC')), new ExchangeId(self::newIdentity()), new OrganizationCorrespondent($prospect->id()), $facts);
        self::getContainer()->get(ExchangeRepository::class)->save($planned);

        return $planned->id()->value;
    }
}
