<?php

declare(strict_types=1);

namespace Tests\App\Prospecting;

use App\Prospecting\Persistence\DynamoToDoRepository;
use App\Prospecting\Persistence\ToDoKeys;
use Prospecter\Core\Prospecting\Application\Port\ToDoListRetriever;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
use Prospecter\Core\Prospecting\Domain\Model\ContactCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\Due;
use Prospecter\Core\Prospecting\Domain\Model\Task;
use Prospecter\Core\Prospecting\Domain\Model\ToDo;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * The to-dos in the calendar of their holder, read from the links `DynamoToDoRepository` writes with every to-do
 * (`TODO_CALENDAR#<member>`): those due on a day of a period, done or not, by their due as written (ADR 0134).
 */
class DynamoToDoCalendarTest extends ApiTestCase
{
    private const string NOW = '2026-11-16 12:00 UTC';
    private const string A_WEEK_IN_NEW_YORK = '2026-11-09T00:00:00-05:00';
    private const string THE_WEEK_AFTER_IN_NEW_YORK = '2026-11-16T00:00:00-05:00';

    private MemberId $holder;
    private AccountId $accountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->holder = new MemberId(self::newIdentity());
        $this->accountId = new AccountId(self::newIdentity());
    }

    public function testThePeriodHoldsTheToDosDueOnItsDaysOnTheClockOfItsReaderByTheirDue(): void
    {
        $atTen = $this->noted('Appeler la RH', new Due('2026-11-14', '10:00'));
        $done = $this->noted('Envoyer mon CV', new Due('2026-11-09'));
        $done->tickOff($this->holder, new \DateTimeImmutable(self::NOW));
        $theDay = $this->noted('Préparer la proposition', new Due('2026-11-14'));
        $before = $this->noted('Relancer', new Due('2026-11-08', '23:30'));
        $after = $this->noted('Rappeler', new Due('2026-11-16'));
        array_map($this->toDos()->save(...), [$atTen, $done, $theDay, $before, $after]);

        $this->assertSame(['Envoyer mon CV', 'Préparer la proposition', 'Appeler la RH'], self::texts($this->calendarOf($this->holder, self::A_WEEK_IN_NEW_YORK, self::THE_WEEK_AFTER_IN_NEW_YORK)));
    }

    public function testARevisedToDoMovesInTheCalendar(): void
    {
        $toDo = $this->noted('Envoyer mon CV', new Due('2026-11-14'));
        $this->toDos()->save($toDo);

        $toDo->revise('Envoyer mon CV', new Due('2026-11-18', '09:00'), $this->holder, new \DateTimeImmutable(self::NOW));
        $this->toDos()->save($toDo);

        $this->assertSame([], $this->calendarOf($this->holder, self::A_WEEK_IN_NEW_YORK, self::THE_WEEK_AFTER_IN_NEW_YORK));
        $this->assertSame(['Envoyer mon CV'], self::texts($this->calendarOf($this->holder, self::THE_WEEK_AFTER_IN_NEW_YORK, '2026-11-23T00:00:00-05:00')));
    }

    public function testAnErasedToDoLeavesTheCalendar(): void
    {
        $toDo = $this->noted('Envoyer mon CV', new Due('2026-11-14'));
        $this->toDos()->save($toDo);

        $this->toDos()->remove($toDo);

        $this->assertSame([], $this->calendarOf($this->holder, self::A_WEEK_IN_NEW_YORK, self::THE_WEEK_AFTER_IN_NEW_YORK));
    }

    public function testTheCalendarOfAColleagueHoldsNoneOfMine(): void
    {
        $this->toDos()->save($this->noted('Envoyer mon CV', new Due('2026-11-14')));

        $this->assertSame([], $this->calendarOf(new MemberId(self::newIdentity()), self::A_WEEK_IN_NEW_YORK, self::THE_WEEK_AFTER_IN_NEW_YORK));
    }

    public function testTheToDosWrittenBeforeTheCalendarsAreInItOnceTheirKeysAreWrittenAgain(): void
    {
        $toDo = $this->noted('Envoyer mon CV', new Due('2026-11-14'));
        $this->toDos()->save($toDo);
        $table = self::tableOf('prospecting');
        $table->batch([], [[ToDoKeys::calendar($this->holder->value), ToDoKeys::toDo($toDo->id()->value)]]);
        $this->assertSame([], $this->calendarOf($this->holder, self::A_WEEK_IN_NEW_YORK, self::THE_WEEK_AFTER_IN_NEW_YORK));

        $this->assertGreaterThanOrEqual(1, self::service(DynamoToDoRepository::class)->writeTheKeysAgain());

        $this->assertSame(['Envoyer mon CV'], self::texts($this->calendarOf($this->holder, self::A_WEEK_IN_NEW_YORK, self::THE_WEEK_AFTER_IN_NEW_YORK)));
    }

    /**
     * @return list<ToDoInList>
     */
    private function calendarOf(MemberId $holder, string $from, string $until): array
    {
        return self::service(ToDoListRetriever::class)->dueIn($holder, new CalendarPeriod(new \DateTimeImmutable($from), new \DateTimeImmutable($until)));
    }

    /**
     * @param list<ToDoInList> $toDos
     *
     * @return list<string>
     */
    private static function texts(array $toDos): array
    {
        return array_map(static fn (ToDoInList $toDo): string => $toDo->text, $toDos);
    }

    private function noted(string $text, Due $due): ToDo
    {
        return ToDo::noted(new Gesture($this->accountId, $this->holder, new \DateTimeImmutable(self::NOW)), new ToDoId(self::newIdentity()), new ContactCorrespondent(new PersonId(self::newIdentity()), [new OrganizationId(self::newIdentity())]), new Task($text, $due));
    }

    private function toDos(): ToDoRepository
    {
        return self::service(ToDoRepository::class);
    }
}
