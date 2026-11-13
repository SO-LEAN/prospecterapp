<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Prospecting\Application\CancelExchange\CancelExchange;
use Prospecter\Core\Prospecting\Application\ConsultUpcomingExchanges\ConsultUpcomingExchanges;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\MarkExchangeDone\MarkExchangeDone;
use Prospecter\Core\Prospecting\Application\MarkExchangeMissed\MarkExchangeMissed;
use Prospecter\Core\Prospecting\Application\MoveExchange\MoveExchange;
use Prospecter\Core\Prospecting\Domain\Model\Canceller;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;
use Tests\Core\Double\StateOf;

/**
 * What a prospector does of a planned exchange, "cette visio" of the scenarios, the last one planned: marked done
 * or missed, moved, cancelled.
 */
final class PlannedExchangeContext implements Context
{
    use SharesTheScenario;

    #[Given('/^(?<done>j\'ai marqué|je marque) cette visio faite$/')]
    public function iMarkItDone(string $done): void
    {
        $this->markDone($this->planned()['facts']->occurredAt, null);
        $this->expectAcceptedWhen($done, 'j\'ai marqué');
    }

    #[When('je marque cette visio faite avec le texte :report')]
    public function iMarkItDoneWithItsReport(string $report): void
    {
        $this->markDone($this->planned()['facts']->occurredAt, $report);
    }

    #[When('/^je marque cette visio faite le (?<day>\S+) à (?<time>\S+)$/')]
    public function iMarkItDoneAt(string $day, string $time): void
    {
        $this->markDone(Vocabulary::frenchMoment(sprintf('%s à %s', $day, $time)), null);
    }

    #[Given('/^(?<done>j\'ai marqué|je marque) cette visio manquée$/')]
    public function iMarkItMissed(string $done): void
    {
        $this->application->dispatch(new MarkExchangeMissed($this->application->signedIn(), $this->scenario->theExchange(), $this->application->now()));
        $this->expectAcceptedWhen($done, 'j\'ai marqué');
    }

    #[Given('/^(?<done>j\'ai annulé|j\'annule) cette visio$/')]
    public function iCancelIt(string $done): void
    {
        $this->cancel(null);
        $this->expectAcceptedWhen($done, 'j\'ai annulé');
    }

    #[When('j\'annule cette visio à la demande du contact')]
    public function iCancelItForTheContact(): void
    {
        $this->cancel(Canceller::Contact);
    }

    #[When('/^je (?:déplace cette visio|la déplace) au (?<day>\S+) à (?<time>\S+)$/')]
    public function iMoveIt(string $day, string $time): void
    {
        $this->application->dispatch(new MoveExchange($this->application->signedIn(), $this->scenario->theExchange(), Vocabulary::frenchMoment(sprintf('%s à %s', $day, $time)), $this->application->now()));
    }

    #[Then('elle n\'est plus dans ses échanges à venir')]
    public function itIsNoLongerUpcoming(): void
    {
        Expect::that(null === $this->application->refusal(), sprintf('Refused: %s', $this->application->refusal()?->getMessage()));
        $contact = $this->planned()['contact'];
        $upcoming = $this->application->consultUpcomingExchanges(new ConsultUpcomingExchanges($this->application->signedIn(), (string) $contact?->value, $this->application->now()));

        Expect::that(!array_any($upcoming->exchanges ?? [], fn (ExchangeInTimeline $exchange): bool => $exchange->id === $this->scenario->theExchange()), 'The exchange is still among the upcoming ones');
    }

    /**
     * @return array<string, mixed> the state of the exchange as it is stored
     */
    private function planned(): array
    {
        $exchange = $this->application->database->exchange($this->scenario->theExchange());
        Expect::that($exchange instanceof Exchange, 'An exchange was expected to be planned');

        return StateOf::the($exchange);
    }

    private function markDone(\DateTimeImmutable $occurredAt, ?string $report): void
    {
        $this->application->dispatch(new MarkExchangeDone($this->application->signedIn(), $this->scenario->theExchange(), $occurredAt, $report, $this->application->now()));
    }

    private function cancel(?Canceller $canceller): void
    {
        $this->application->dispatch(new CancelExchange($this->application->signedIn(), $this->scenario->theExchange(), $canceller?->value, $this->application->now()));
    }

    /**
     * A step telling what was done before arranges the scenario: refused, the scenario is wrong.
     */
    private function expectAcceptedWhen(string $step, string $arranging): void
    {
        if ($arranging === $step) {
            Expect::that(null === $this->application->refusal(), sprintf('Refused: %s', $this->application->refusal()?->getMessage()));
        }
    }
}
