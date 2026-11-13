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
use Prospecter\Core\Prospecting\Application\ConsultCalendar\ConsultCalendar;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\NoteExchange\NoteExchange;
use Prospecter\Core\Prospecting\Application\PlanExchange\PlanExchange;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
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

    /** @var list<ExchangeInTimeline>|null */
    private ?array $calendar = null;

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
        $this->plan($colleague->id()->value, $exchange, $contact, self::moment($day, $time));
    }

    #[When('/^je consulte mon calendrier du (?<first>\S+) au (?<last>\S+)$/')]
    public function iConsultMyCalendar(string $first, string $last): void
    {
        $this->calendar = $this->application->consultCalendar(new ConsultCalendar($this->application->signedIn(), self::moment($first, '0h00'), self::moment($last, '0h00')->modify('+1 day')))?->exchanges;
    }

    #[Then('il montre, dans l\'ordre :')]
    public function itShows(TableNode $table): void
    {
        $columns = array_flip($table->getRow(0));

        Expect::same($table->getColumnsHash(), array_map(fn (ExchangeInTimeline $exchange): array => array_replace($columns, array_intersect_key($this->describe($exchange), $columns)), $this->calendar()));
    }

    #[Then('il ne montre rien')]
    public function itShowsNothing(): void
    {
        Expect::same([], $this->calendar());
    }

    /**
     * @return list<ExchangeInTimeline>
     */
    private function calendar(): array
    {
        Expect::that(null !== $this->calendar, sprintf('A calendar was expected, got: %s', $this->application->refusal()?->getMessage()));

        return $this->calendar;
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
            'statut' => Vocabulary::describeStatus($exchange->status),
        ];
    }
}
