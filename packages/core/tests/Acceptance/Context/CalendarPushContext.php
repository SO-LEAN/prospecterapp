<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Connectors\Domain\Model\CalendarEvent;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * The calendar « Prospecter » of the member at Google, as the exchanges they plan are pushed into it (ADR 0127):
 * « l'événement » of the scenarios is that of the last exchange planned.
 */
final class CalendarPushContext implements Context
{
    use SharesTheScenario;

    private const string TITLE = '/^mon agenda Google a l\'événement « (?<title>[^»]+) » le (?<day>\S+) de (?<from>\S+) à (?<until>\S+)$/u';

    #[Given('Google refuse désormais mon jeton')]
    public function googleRefusesMyTokenFromNowOn(): void
    {
        $this->application->providerAccess->refuseEveryToken();
    }

    #[Given('j\'ai supprimé l\'agenda « Prospecter » de Google')]
    public function iDeletedTheCalendarOfProspecter(): void
    {
        $this->application->calendars->deleteEveryCalendar();
    }

    #[When('la planification de la visio est poussée de nouveau')]
    public function thePlanningIsPushedAgain(): void
    {
        $published = $this->application->events->published(ExchangeFactsChanged::class);
        Expect::that([] !== $published, 'No fact of the exchange was published');
        $this->application->publishAgain(array_last($published));
    }

    #[Then(self::TITLE)]
    public function myCalendarHasTheEvent(string $title, string $day, string $from, string $until): void
    {
        $event = $this->theEvent();
        Expect::same(
            ['title' => $title, 'from' => Vocabulary::frenchMoment(sprintf('%s à %s', $day, $from))->format(\DATE_ATOM), 'until' => Vocabulary::frenchMoment(sprintf('%s à %s', $day, $until))->format(\DATE_ATOM)],
            ['title' => $this->titleOf($event), 'from' => $event->startsAt->format(\DATE_ATOM), 'until' => $event->endsAt->format(\DATE_ATOM)],
        );
    }

    #[Then('l\'événement a lieu à :venue')]
    public function theEventTakesPlaceAt(string $venue): void
    {
        Expect::same($venue, $this->theEvent()->venue);
    }

    #[Then('l\'événement mène à la fiche de :name')]
    public function theEventLeadsToTheCardOf(string $name): void
    {
        $event = $this->theEvent();
        $card = $this->scenario->isAnOrganization($name)
            ? ['contact' => null, 'organization' => $this->scenario->organizationNamed($name)->id()->value]
            : ['contact' => $this->scenario->personIdOf($name), 'organization' => $event->organization->value];

        Expect::same($card, ['contact' => $event->contact?->value, 'organization' => $event->organization->value]);
    }

    #[Then('l\'événement me rappelle :minutes minutes avant')]
    public function theEventRemindsMe(int $minutes): void
    {
        Expect::same($minutes, $this->theEvent()->reminderMinutesBefore);
    }

    #[Then('l\'événement ne me rappelle pas')]
    public function theEventDoesNotRemindMe(): void
    {
        Expect::same(null, $this->theEvent()->reminderMinutesBefore);
    }

    #[Then('l\'événement n\'est plus dans mon agenda Google')]
    public function theEventIsNoLongerInMyCalendar(): void
    {
        Expect::that(null === $this->application->refusal(), sprintf('Refused: %s', $this->application->refusal()?->getMessage()));
        Expect::that(null === $this->application->calendars->eventOf($this->scenario->theExchange()), 'The event was expected to be withdrawn from Google');
        Expect::that($this->application->calendars->wasWithdrawn($this->scenario->theExchange()), 'The event was expected to have been pushed, then withdrawn');
    }

    #[Then('rien n\'est retiré de Google pour cet appel')]
    public function nothingIsWithdrawnForIt(): void
    {
        Expect::that(null === $this->application->refusal(), sprintf('Refused: %s', $this->application->refusal()?->getMessage()));
        Expect::that(!$this->application->calendars->wasWithdrawn($this->scenario->theExchange()), 'Nothing was pushed for it: nothing was expected to be withdrawn');
    }

    #[Then('rien n\'est poussé dans Google')]
    public function nothingIsPushed(): void
    {
        Expect::same([], $this->application->calendars->events());
    }

    #[Then('/^mon agenda Google a un seul événement, « (?<title>[^»]+) »$/u')]
    public function myCalendarHasASingleEvent(string $title): void
    {
        Expect::same([$title], array_values(array_map($this->titleOf(...), $this->application->calendars->events())));
    }

    private function theEvent(): CalendarEvent
    {
        $event = $this->application->calendars->eventOf($this->scenario->theExchange());
        Expect::that(null !== $event, sprintf('The exchange was expected in my calendar at Google, got: %s', $this->application->refusal()?->getMessage() ?? 'nothing'));

        return $event;
    }

    /**
     * As the adapter of Google words it: « Visio · Ada Lovelace (ACME) », or « Rendez-vous · ACME » with the
     * organization itself.
     */
    private function titleOf(CalendarEvent $event): string
    {
        $channel = Vocabulary::describeChannel($event->channel->value);

        return null === $event->contactName
            ? sprintf('%s · %s', $channel, $event->organizationName)
            : sprintf('%s · %s (%s)', $channel, $event->contactName, $event->organizationName);
    }
}
