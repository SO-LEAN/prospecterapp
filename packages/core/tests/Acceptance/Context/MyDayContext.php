<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultMyDay\ConsultMyDay;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultMyDay\MyDay;
use Prospecter\Core\Prospecting\Application\Exchange\PlanExchange\PlanExchange;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * The signed-in prospector's day, on their dashboard: what they planned today, then the next upcoming exchanges.
 * A scenario names an exchange by its channel and its hour: « l'appel de 9h00 ».
 */
final class MyDayContext implements Context
{
    use SharesTheScenario;
    use DescribesInterlocutors;

    /** The channels, as a sentence names one exchange made through them. */
    private const array SHOWN = [
        "l'appel" => 'call',
        'le rendez-vous' => 'meeting',
        'la visio' => 'videoCall',
    ];
    private const string ANY_SHOWN = "l'appel|le rendez-vous|la visio";
    private const string NEXT_HOUR = '10h00';

    private ?MyDay $myDay = null;

    /** @var list<\DateTimeImmutable> the meetings planned on the following days, the soonest first */
    private array $meetingsToCome = [];

    #[Given('/^j\'ai planifié avec "(?<contact>[^"]+)" un appel le (?<callDay>\S+) à (?<callTime>\S+), une visio le (?<videoCallDay>\S+) à (?<videoCallTime>\S+) et (?<meetings>\d+) rendez-vous les jours suivants$/')]
    public function iPlannedTheDay(string $contact, string $callDay, string $callTime, string $videoCallDay, string $videoCallTime, int $meetings): void
    {
        $this->plan($contact, 'call', self::moment($callDay, $callTime));
        $videoCallAt = self::moment($videoCallDay, $videoCallTime);
        $this->plan($contact, 'videoCall', $videoCallAt);

        for ($day = 1; $day <= $meetings; ++$day) {
            $meetingAt = self::moment($videoCallAt->modify(sprintf('+%d days', $day))->format('d/m/Y'), self::NEXT_HOUR);
            $this->plan($contact, 'meeting', $meetingAt);
            $this->meetingsToCome[] = $meetingAt;
        }
    }

    #[When('je consulte le tableau de bord')]
    public function iConsultMyDashboard(): void
    {
        $today = $this->application->now()->setTime(0, 0);
        $this->myDay = $this->application->consultMyDay(new ConsultMyDay($this->application->signedIn(), $today, $today->modify('+1 day'), $this->application->now()));
    }

    #[Then('/^il montre aujourd\'hui (?<exchanges>.+)$/')]
    public function itShowsToday(string $exchanges): void
    {
        Expect::same(explode(' puis ', $exchanges), array_map(self::describe(...), $this->myDay()->today));
    }

    #[Then('il montre aujourd\'hui, avec :')]
    public function itShowsTodayWith(TableNode $table): void
    {
        Expect::same($table->getColumnsHash(), array_map(fn (ExchangeInTimeline $exchange): array => ['échange' => self::describe($exchange), ...$this->shownInterlocutorOf($exchange, $this->myDay()->interlocutors)], $this->myDay()->today));
    }

    #[Then('/^les (?<count>\d+) prochains rendez-vous, du plus proche au plus lointain$/')]
    public function theNextMeetings(int $count): void
    {
        Expect::same(
            array_map(static fn (\DateTimeImmutable $at): array => ['meeting', Vocabulary::describeFrenchMoment($at)], array_slice($this->meetingsToCome, 0, $count)),
            array_map(static fn (ExchangeInTimeline $exchange): array => [$exchange->channel, Vocabulary::describeFrenchMoment($exchange->occurredAt)], $this->myDay()->next),
        );
    }

    #[Then('/^(?<toHandle>'.self::ANY_SHOWN.') de (?<toHandleTime>\S+) est à traiter, (?<notToHandle>'.self::ANY_SHOWN.') de (?<notToHandleTime>\S+) ne l\'est pas$/')]
    public function oneIsToHandle(string $toHandle, string $toHandleTime, string $notToHandle, string $notToHandleTime): void
    {
        $toHandleNow = array_map(self::describe(...), array_filter($this->myDay()->today, fn (ExchangeInTimeline $exchange): bool => in_array($exchange->id, $this->myDay()->toHandle, true)));

        Expect::same([sprintf('%s de %s', $toHandle, $toHandleTime)], array_values($toHandleNow));
        Expect::that(!in_array(sprintf('%s de %s', $notToHandle, $notToHandleTime), $toHandleNow, true), sprintf('%s de %s is not to handle', $notToHandle, $notToHandleTime));
    }

    #[Then('il ne montre pas cette visio')]
    public function itDoesNotShowTheVideoCall(): void
    {
        $shown = [...$this->myDay()->today, ...$this->myDay()->next];

        Expect::same([], array_values(array_filter($shown, static fn (ExchangeInTimeline $exchange): bool => 'videoCall' === $exchange->channel)));
    }

    #[Then('il ne montre rien aujourd\'hui')]
    public function itShowsNothingToday(): void
    {
        Expect::same([], $this->myDay()->today);
    }

    private function myDay(): MyDay
    {
        return Expect::present($this->myDay, sprintf('My day was expected, got: %s', $this->application->refusal()?->getMessage()));
    }

    /**
     * The exchange is planned the day before. So it is still to come when it is planned, whatever the scenario's
     * moment.
     */
    private function plan(string $contact, string $channel, \DateTimeImmutable $plannedAt): void
    {
        $this->application->dispatch(new PlanExchange($this->application->signedIn(), $this->application->newIdentity(), $this->scenario->personIdOf($contact), $channel, $plannedAt, null, null, $plannedAt->modify('-1 day')));
        Expect::that(null === $this->application->refusal(), sprintf('Refused: %s', $this->application->refusal()?->getMessage()));
    }

    private static function moment(string $day, string $time): \DateTimeImmutable
    {
        return Vocabulary::frenchMoment(sprintf('%s à %s', $day, $time));
    }

    private static function describe(ExchangeInTimeline $exchange): string
    {
        return sprintf('%s de %s', array_search($exchange->channel, self::SHOWN, true), $exchange->occurredAt->format('G\hi'));
    }
}
