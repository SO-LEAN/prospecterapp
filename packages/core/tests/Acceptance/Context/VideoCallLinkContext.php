<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Then;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ConsultExchange;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Tests\Core\Acceptance\Expect;

/**
 * The link Google Meet the calendar of the member creates for a video call planned asking for one (ADR 0137): « la
 * visio » of the scenarios is the last exchange planned, read again as its detail shows it.
 */
final class VideoCallLinkContext implements Context
{
    use SharesTheScenario;

    #[Then('une fois poussée, la visio a un lien Meet')]
    public function oncePushedTheVideoCallHasAMeetLink(): void
    {
        $created = $this->application->calendars->linkCreatedFor($this->scenario->theExchange());
        Expect::that(null !== $created, 'Google was expected to create a Meet link');

        Expect::same($created, $this->theVideoCall()->link);
    }

    #[Then('l\'événement a lieu à ce lien Meet')]
    public function theEventTakesPlaceAtThatMeetLink(): void
    {
        $event = $this->application->calendars->eventOf($this->scenario->theExchange());

        Expect::same($this->theVideoCall()->link, $event?->venue);
    }

    #[Then('son lien reste :link')]
    public function itsLinkStays(string $link): void
    {
        Expect::same($link, $this->theVideoCall()->link);
    }

    #[Then('la visio n\'a pas de lien')]
    public function theVideoCallHasNoLink(): void
    {
        Expect::same(null, $this->theVideoCall()->link);
    }

    #[Then('elle attend son lien')]
    public function itAwaitsItsLink(): void
    {
        Expect::that($this->theVideoCall()->linkAwaited, 'The video call was expected to await its link');
    }

    #[Then('Google n\'a créé aucun lien Meet')]
    public function googleCreatedNoMeetLink(): void
    {
        Expect::same(0, $this->application->calendars->linksAsked());
    }

    #[Then('Google a créé un seul lien Meet')]
    public function googleCreatedASingleMeetLink(): void
    {
        Expect::same(1, $this->application->calendars->linksAsked());
    }

    private function theVideoCall(): ExchangeDetail
    {
        $detail = $this->application->consultExchange(new ConsultExchange($this->application->signedIn(), $this->scenario->theExchange(), $this->application->now()));
        Expect::that(null !== $detail, sprintf('The video call was expected, got: %s', $this->application->refusal()?->getMessage()));

        return $detail;
    }
}
