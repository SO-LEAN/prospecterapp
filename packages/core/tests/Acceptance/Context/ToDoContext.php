<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Prospecting\Application\ConsultMyToDos\ConsultMyToDos;
use Prospecter\Core\Prospecting\Application\ConsultOrganizationToDos\ConsultOrganizationToDos;
use Prospecter\Core\Prospecting\Application\ConsultToDos\ConsultToDos;
use Prospecter\Core\Prospecting\Application\EraseToDo\EraseToDo;
use Prospecter\Core\Prospecting\Application\NoteOrganizationToDo\NoteOrganizationToDo;
use Prospecter\Core\Prospecting\Application\NoteToDo\NoteToDo;
use Prospecter\Core\Prospecting\Application\ReviseToDo\ReviseToDo;
use Prospecter\Core\Prospecting\Application\TickOffToDo\TickOffToDo;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\Prospecting\Application\UntickToDo\UntickToDo;
use Prospecter\Core\Prospecting\Domain\Event\ToDoLeftWithoutOrganization;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * The to-dos of the contacts and of the organizations: « elle » of the scenarios is the last one noted.
 */
final class ToDoContext implements Context
{
    use SharesTheScenario;

    private const string FRENCH_DAY = 'd/m/Y';

    private ?string $lastNoted = null;
    private string $lastNotedFor = '';
    /** @var array<string, string> the to-dos noted, by their text */
    private array $noted = [];
    /** @var list<ToDoInList>|null */
    private ?array $mine = null;

    #[Given('/^(?<done>j\'ai noté|je note) à faire pour "(?<for>[^"]+)" "(?<text>[^"]*)" pour le (?<day>\S+)(?: à (?<time>\S+))?$/')]
    public function iNoteAToDo(string $done, string $for, string $text, string $day, string $time = ''): void
    {
        $this->note($this->application->signedIn(), $for, $text, $day, $time);

        if ('j\'ai noté' === $done) {
            $this->expectNoted();
        }
    }

    #[Given('/^"(?<member>[^"]+)" a noté à faire pour "(?<for>[^"]+)" "(?<text>[^"]*)" pour le (?<day>\S+)$/')]
    public function aMemberNotedAToDo(string $member, string $for, string $text, string $day): void
    {
        $this->note($this->scenario->memberIdOf($member)->value, $for, $text, $day, '');
        $this->expectNoted();
    }

    #[When('je la coche')]
    public function iTickItOff(): void
    {
        $this->application->dispatch(new TickOffToDo($this->application->signedIn(), $this->theToDo(), $this->application->now()));
    }

    #[Given('je l\'ai cochée')]
    public function iTickedItOff(): void
    {
        $this->iTickItOff();
        $this->scenario->itIsNotRefused();
    }

    #[Given('j\'ai coché :text')]
    public function iTickedOff(string $text): void
    {
        $this->application->dispatch(new TickOffToDo($this->application->signedIn(), $this->noted[$text] ?? throw new \LogicException(sprintf('No to-do "%s" in the scenario', $text)), $this->application->now()));
        $this->scenario->itIsNotRefused();
    }

    #[When('je la décoche')]
    public function iUntickIt(): void
    {
        $this->application->dispatch(new UntickToDo($this->application->signedIn(), $this->theToDo(), $this->application->now()));
    }

    #[When('/^je la modifie en "(?<text>[^"]*)" pour le (?<day>\S+)(?: à (?<time>\S+))?$/')]
    public function iReviseIt(string $text, string $day, string $time = ''): void
    {
        $this->application->dispatch(new ReviseToDo($this->application->signedIn(), $this->theToDo(), $text, self::dayOf($day), self::timeOf($time), $this->application->now()));
    }

    #[When('je la supprime')]
    public function iEraseIt(): void
    {
        $this->application->dispatch(new EraseToDo($this->application->signedIn(), $this->theToDo(), $this->application->now()));
    }

    #[When('je consulte mes actions à faire')]
    public function iConsultMyToDos(): void
    {
        $this->mine = $this->application->consultMyToDos(new ConsultMyToDos($this->application->signedIn()))?->toDos;
    }

    #[When('l\'action à faire à effacer est publiée de nouveau')]
    public function theToDoToEraseIsPublishedAgain(): void
    {
        $published = $this->application->events->published(ToDoLeftWithoutOrganization::class);
        Expect::that([] !== $published, 'No to-do to erase was published');
        $this->application->publishAgain(array_last($published));
    }

    #[Then('les actions à faire de :name sont :')]
    public function theToDosAre(string $name, TableNode $table): void
    {
        $columns = array_flip($table->getRow(0));

        Expect::same($table->getColumnsHash(), array_map(static fn (ToDoInList $toDo): array => array_replace($columns, array_intersect_key(self::describe($toDo), $columns)), $this->toDosOf($name)));
    }

    #[Then('mes actions à faire sont :')]
    public function myToDosAre(TableNode $table): void
    {
        $columns = array_flip($table->getRow(0));

        Expect::same($table->getColumnsHash(), array_map(static fn (ToDoInList $toDo): array => array_replace($columns, array_intersect_key(self::describe($toDo), $columns)), $this->mine()));
    }

    #[Then('/^son échéance est le (?<day>\S+) à (?<time>\S+)$/')]
    public function itIsDueOn(string $day, string $time): void
    {
        $toDo = $this->stored();

        Expect::same([self::dayOf($day), self::timeOf($time)], [$toDo?->dueOn, $toDo?->dueAt]);
    }

    #[Then('/^elle est faite, le (?<day>\S+) à (?<time>\S+)$/')]
    public function itIsDone(string $day, string $time): void
    {
        $this->scenario->itIsNotRefused();

        $doneAt = $this->stored()?->doneAt;

        Expect::same(Vocabulary::describeFrenchMoment(Vocabulary::frenchMoment(sprintf('%s à %s', $day, $time))), null === $doneAt ? null : Vocabulary::describeFrenchMoment($doneAt));
    }

    #[Then('elle est dans les actions à faire de :name')]
    public function itIsAmongTheToDosOf(string $name): void
    {
        $this->expectNoted();

        Expect::that(array_any($this->toDosOf($name), fn (ToDoInList $toDo): bool => $toDo->id === $this->lastNoted), sprintf('The to-do is not among those of %s', $name));
    }

    #[Then('elle n\'y est pas')]
    public function itIsNotAmongMine(): void
    {
        Expect::that(!array_any($this->mine(), fn (ToDoInList $toDo): bool => $toDo->id === $this->lastNoted), 'The to-do is among mine');
    }

    #[Then('elle est sur la fiche de :contact, au nom de :member')]
    public function itIsOnTheCardOfHeldBy(string $contact, string $member): void
    {
        $toDo = array_find($this->toDosOf($contact), fn (ToDoInList $toDo): bool => $toDo->id === $this->lastNoted);

        Expect::same($this->scenario->memberIdOf($member)->value, $toDo?->heldBy);
    }

    #[Then('elle n\'existe plus')]
    public function itIsGone(): void
    {
        Expect::that(null !== $this->lastNoted, 'A to-do was expected to be noted');
        Expect::same(null, $this->application->database->toDo((string) $this->lastNoted));
    }

    #[Then(':name n\'a plus d\'action à faire')]
    public function hasNoToDoAnyMore(string $name): void
    {
        Expect::same([], $this->toDosOf($name));
    }

    private function note(string $prospectorId, string $for, string $text, string $day, string $time): void
    {
        $id = $this->application->newIdentity();
        $this->application->dispatch($this->scenario->isAnOrganization($for)
            ? new NoteOrganizationToDo($prospectorId, $id, $this->scenario->idOf($for), $text, self::dayOf($day), self::timeOf($time), $this->application->now())
            : new NoteToDo($prospectorId, $id, $this->scenario->personIdOf($for), $text, self::dayOf($day), self::timeOf($time), $this->application->now()));
        $this->lastNoted = null === $this->application->refusal() ? $id : null;
        $this->lastNotedFor = $for;
        $this->noted[$text] = $id;
    }

    private function expectNoted(): void
    {
        Expect::that(null !== $this->lastNoted, sprintf('A to-do was expected to be noted, got: %s', $this->application->refusal()?->getMessage()));
    }

    private function theToDo(): string
    {
        $this->expectNoted();

        return (string) $this->lastNoted;
    }

    /**
     * The last one noted, as the list of its contact or of its organization reads it.
     */
    private function stored(): ?ToDoInList
    {
        $this->expectNoted();

        return array_find($this->toDosOf($this->lastNotedFor), fn (ToDoInList $toDo): bool => $toDo->id === $this->lastNoted);
    }

    /**
     * @return list<ToDoInList>
     */
    private function toDosOf(string $name): array
    {
        $toDos = $this->scenario->isAnOrganization($name)
            ? $this->application->consultOrganizationToDos(new ConsultOrganizationToDos($this->scenario->idOf($name)))
            : $this->application->consultToDos(new ConsultToDos($this->application->signedIn(), $this->scenario->personIdOf($name)));
        Expect::that(null !== $toDos, sprintf('The to-dos were expected, got: %s', $this->application->refusal()?->getMessage()));

        return $toDos->toDos;
    }

    /**
     * @return list<ToDoInList>
     */
    private function mine(): array
    {
        Expect::that(null !== $this->mine, sprintf('My to-dos were expected, got: %s', $this->application->refusal()?->getMessage()));

        return $this->mine;
    }

    private static function dayOf(string $frenchDay): string
    {
        $day = \DateTimeImmutable::createFromFormat('!'.self::FRENCH_DAY, $frenchDay);

        return false === $day ? $frenchDay : $day->format('Y-m-d');
    }

    private static function timeOf(string $frenchTime): ?string
    {
        return '' === $frenchTime ? null : (string) preg_replace_callback('/^(\d{1,2})h(\d{2})$/', static fn (array $hour): string => sprintf('%02d:%s', $hour[1], $hour[2]), $frenchTime);
    }

    /**
     * @return array<string, string>
     */
    private static function describe(ToDoInList $toDo): array
    {
        $day = \DateTimeImmutable::createFromFormat('!Y-m-d', $toDo->dueOn);

        return [
            'échéance' => trim(sprintf('%s %s', false === $day ? $toDo->dueOn : $day->format(self::FRENCH_DAY), $toDo->dueAt ?? '')),
            'texte' => $toDo->text,
            'faite' => Vocabulary::describeYesOrNo($toDo->isDone()),
        ];
    }
}
