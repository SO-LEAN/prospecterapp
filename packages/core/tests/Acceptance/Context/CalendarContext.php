<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Prospecting\Application\CancelExchange\CancelExchange;
use Prospecter\Core\Prospecting\Application\ConsultCalendar\Calendar;
use Prospecter\Core\Prospecting\Application\ConsultCalendar\ConsultCalendar;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\NoteExchange\NoteExchange;
use Prospecter\Core\Prospecting\Application\PlanExchange\PlanExchange;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * The calendar of the signed-in prospector, read period by period: a period is told by its first and its last day.
 */
final class CalendarContext implements Context
{
    use SharesTheScenario;

    private const string ANY_PLANNED = 'un appel|un rendez-vous|une visio';

    private ?Calendar $calendar = null;

    #[Given('/^j\'ai noté (?<exchange>'.ExchangeContext::ANY_EXCHANGE.') avec "(?<contact>[^"]+)" le (?<day>\S+) à (?<time>\S+)$/')]
    public function iNotedAnExchange(string $exchange, string $contact, string $day, string $time): void
    {
        $this->application->dispatch(new NoteExchange($this->application->signedIn(), $this->application->newIdentity(), $this->scenario->personIdOf($contact), Vocabulary::channelOfAnExchange($exchange), Direction::Outbound->value, self::moment($day, $time), null, false, $this->application->now()));
        $this->expectAccepted();
    }

    #[Given('/^j\'ai annulé (?<exchange>'.self::ANY_PLANNED.') avec "(?<contact>[^"]+)" le (?<day>\S+) à (?<time>\S+)$/')]
    public function iCancelledAPlannedExchange(string $exchange, string $contact, string $day, string $time): void
    {
        $id = $this->plan($this->application->signedIn(), $exchange, $contact, self::moment($day, $time));
        $this->application->dispatch(new CancelExchange($this->application->signedIn(), $id, null, $this->application->now()));
        $this->expectAccepted();
    }

    #[Given('/^un autre prospecteur de "(?<account>[^"]+)" a planifié (?<exchange>'.self::ANY_PLANNED.') avec "(?<contact>[^"]+)" le (?<day>\S+) à (?<time>\S+)$/')]
    public function anotherProspectorPlanned(string $account, string $exchange, string $contact, string $day, string $time): void
    {
        $colleague = $this->scenario->aMemberOf('un autre prospecteur', $account, Role::User, Role::Prospector, Role::PersonalData);
        $this->scenario->rememberTheExchange($this->plan($colleague->id()->value, $exchange, $contact, self::moment($day, $time)));
    }

    #[When('/^je consulte mon calendrier du (?<first>\S+) au (?<last>\S+)$/')]
    public function iConsultMyCalendar(string $first, string $last): void
    {
        $this->calendar = $this->application->consultCalendar(new ConsultCalendar($this->application->signedIn(), $this->startOf($first), $this->startOf($last)->modify('+1 day'), $this->application->readingNow()));
    }

    #[Then('il montre, dans l\'ordre :')]
    public function itShows(TableNode $table): void
    {
        $columns = array_flip($table->getRow(0));

        Expect::same($table->getColumnsHash(), array_map(fn (ExchangeInTimeline $exchange): array => array_replace($columns, array_intersect_key($this->describe($exchange), $columns)), $this->calendar()->exchanges));
    }

    #[Then('il ne montre rien')]
    public function itShowsNothing(): void
    {
        Expect::same([[], []], [$this->calendar()->exchanges, $this->calendar()->toDos]);
    }

    #[Then('/^il montre "(?<text>[^"]+)" le (?<day>\S+?)(?: à (?<time>\S+?))?(?:, (?<state>toute la journée|faite, que je peux décocher|en retard|pas en retard))?$/')]
    public function itShowsTheToDo(string $text, string $day, string $time = '', string $state = ''): void
    {
        $toDo = array_find($this->calendar()->toDos, static fn (ToDoInList $toDo): bool => $toDo->text === $text);
        $toDo = Expect::present($toDo, sprintf('The calendar does not show "%s"', $text));

        Expect::same([self::dayOf($day), '' === $time ? null : self::timeOf($time)], [$toDo->dueOn, $toDo->dueAt]);
        match ($state) {
            'faite, que je peux décocher' => Expect::same([true, true], [$toDo->isDone(), in_array('UntickToDo', $toDo->possibleIntents, true)]),
            'en retard', 'pas en retard' => Expect::same('en retard' === $state, in_array($toDo->id, $this->calendar()->late, true)),
            default => null,
        };
    }

    #[Then('/^il montre (?<exchange>l\'appel|le rendez-vous|la visio) avec "(?<contact>[^"]+)" le (?<day>\S+) à (?<time>\S+), (?<state>en retard|pas en retard)$/')]
    public function itShowsTheExchange(string $exchange, string $contact, string $day, string $time, string $state): void
    {
        $shown = array_find($this->calendar()->exchanges, fn (ExchangeInTimeline $shown): bool => $shown->personId === $this->scenario->personIdOf($contact) && $shown->occurredAt == self::moment($day, $time));
        $shown = Expect::present($shown, sprintf('The calendar does not show %s with %s on %s at %s', $exchange, $contact, $day, $time));

        Expect::same('en retard' === $state, in_array($shown->id, $this->calendar()->late, true));
    }

    #[Then('/^il montre (?:l\'appel|le rendez-vous|la visio) du (?<day>\S+) à (?<time>\S+), (?<origin>réservée? par mon lien public|que j\'ai planifiée?)$/')]
    public function itShowsTheExchangeAndWhoArrangedIt(string $day, string $time, string $origin): void
    {
        $shown = array_find($this->calendar()->exchanges, static fn (ExchangeInTimeline $shown): bool => $shown->occurredAt == self::moment($day, $time));
        $shown = Expect::present($shown, sprintf('The calendar does not show an exchange on %s at %s', $day, $time));

        Expect::same(str_starts_with($origin, 'réservé'), $shown->bookedByLink);
    }

    private function calendar(): Calendar
    {
        return Expect::present($this->calendar, sprintf('A calendar was expected, got: %s', $this->application->refusal()?->getMessage()));
    }

    private function plan(string $prospectorId, string $exchange, string $contact, \DateTimeImmutable $plannedAt): string
    {
        $id = $this->application->newIdentity();
        $this->application->dispatch(new PlanExchange($prospectorId, $id, $this->scenario->personIdOf($contact), Vocabulary::channelOfAnExchange($exchange), $plannedAt, null, null, $this->application->now()));
        $this->expectAccepted();

        return $id;
    }

    private function expectAccepted(): void
    {
        Expect::that(null === $this->application->refusal(), sprintf('Refused: %s', $this->application->refusal()?->getMessage()));
    }

    /**
     * The start of a day on the clock of the reader.
     */
    private function startOf(string $day): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat('!d/m/Y', $day, $this->application->readerClock()) ?: throw new \LogicException(sprintf('"%s" is no day', $day));
    }

    private static function dayOf(string $frenchDay): string
    {
        return self::moment($frenchDay, '0h00')->format('Y-m-d');
    }

    private static function timeOf(string $frenchTime): string
    {
        return self::moment('01/01/2026', $frenchTime)->format('H:i');
    }

    private static function moment(string $day, string $time): \DateTimeImmutable
    {
        return Vocabulary::frenchMoment(sprintf('%s à %s', $day, $time));
    }

    /**
     * @return array<string, string>
     */
    private function describe(ExchangeInTimeline $exchange): array
    {
        return [
            'moment' => Vocabulary::describeFrenchMoment($exchange->occurredAt),
            'fin' => $exchange->occurredAt->modify(sprintf('+%d minutes', $exchange->durationInMinutes ?? 0))->format('H:i'),
            'canal' => Vocabulary::describeChannel($exchange->channel),
            'avec' => null === $exchange->personId ? '' : $this->scenario->nameOfPersonAsRead(new PersonId($exchange->personId)),
            'de' => $this->scenario->nameOf(new OrganizationId($exchange->organizationIds[0])),
            'signalé' => $exchange->textFlagged ? 'oui' : 'non',
            'statut' => Vocabulary::describeStatus($exchange->status),
            'déplaçable' => in_array($exchange->id, $this->calendar()->movable, true) ? 'oui' : 'non',
        ];
    }
}
