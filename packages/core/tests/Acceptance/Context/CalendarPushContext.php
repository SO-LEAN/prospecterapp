<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Hook\BeforeStep;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Connectors\Domain\Model\CalendarEvent;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\SharedKernel\Domain\Contact\PhoneNumber;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * The member's calendar « Prospecter » at Google, as the exchanges they plan are pushed into it (ADR 0127). The
 * scenarios' « l'événement » is the event of the last exchange planned.
 */
final class CalendarPushContext implements Context
{
    use SharesTheScenario;

    private const array NOTICES = ['l\'invitation' => 'invitation', 'la mise à jour' => 'update', 'l\'annulation' => 'cancellation'];
    private const string GESTURE = 'When';
    private const string TITLE = '/^mon agenda Google a l\'événement « (?<title>[^»]+) » le (?<day>\S+) de (?<from>\S+) à (?<until>\S+)$/u';

    private int $noticesBeforeTheGesture = 0;
    private string $previousStepType = '';

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
        $this->application->publishAgain(Expect::present(array_last($published), 'No fact of the exchange was published'));
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
            : ['contact' => $this->scenario->personIdOf($name), 'organization' => $event->organization?->value];

        Expect::same($card, ['contact' => $event->contact?->value, 'organization' => $event->organization?->value]);
    }

    #[Then('l\'événement invite :email')]
    public function theEventInvites(string $email): void
    {
        Expect::same($email, $this->theEvent()->guest?->email->value);
    }

    #[Then('l\'événement n\'invite personne')]
    public function theEventInvitesNobody(): void
    {
        Expect::same(null, $this->theEvent()->guest);
    }

    #[Then('/^l\'événement dit que "(?<caller>[^"]+)" l\'appellera au (?<phone>[^a-z]+)$/u')]
    public function theEventSaysWhoWillCallAt(string $caller, string $phone): void
    {
        $guest = $this->theEvent()->guest;

        Expect::same([$caller, PhoneNumber::of(trim($phone))->value], [$guest?->calledBy, $guest?->calledAt?->value]);
    }

    /**
     * What Google sends the guests is read from the scenario's gesture on, that is from its first step « Quand ».
     */
    #[BeforeStep]
    public function markTheNoticesSentBeforeTheGesture(BeforeStepScope $scope): void
    {
        $type = $scope->getStep()->getKeywordType();
        if (self::GESTURE === $type && self::GESTURE !== $this->previousStepType) {
            $this->noticesBeforeTheGesture = count($this->application->calendars->noticesSent());
        }
        $this->previousStepType = $type;
    }

    #[Then('/^Google envoie (?<notice>l\'invitation|la mise à jour|l\'annulation) à "(?<email>[^"]+)"$/u')]
    public function googleSends(string $notice, string $email): void
    {
        Expect::same([[self::NOTICES[$notice], $email]], $this->noticesSentByTheGesture());
    }

    #[Then('Google n\'envoie rien à :email')]
    public function googleSendsNothing(string $email): void
    {
        Expect::same([], array_filter($this->noticesSentByTheGesture(), static fn (array $notice): bool => $email === $notice[1]));
    }

    /**
     * @return list<array{string, string}>
     */
    private function noticesSentByTheGesture(): array
    {
        return array_slice($this->application->calendars->noticesSent(), $this->noticesBeforeTheGesture);
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

        return Expect::present($event, sprintf('The exchange was expected in my calendar at Google, got: %s', $this->application->refusal()?->getMessage() ?? 'nothing'));
    }

    /**
     * The title as Google's adapter words it: « Visio · Ada Lovelace (ACME) ». With the organization itself, it is
     * « Rendez-vous · ACME ».
     */
    private function titleOf(CalendarEvent $event): string
    {
        $channel = Vocabulary::describeChannel($event->channel->value);

        return match (true) {
            null === $event->contactName => sprintf('%s · %s', $channel, $event->organizationName),
            null === $event->organizationName => sprintf('%s · %s', $channel, $event->contactName),
            default => sprintf('%s · %s (%s)', $channel, $event->contactName, $event->organizationName),
        };
    }
}
