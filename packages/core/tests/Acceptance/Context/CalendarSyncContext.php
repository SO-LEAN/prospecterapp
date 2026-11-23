<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Connectors\Application\ExchangeSync;
use Prospecter\Core\Connectors\Application\MyConnection;
use Prospecter\Core\Connectors\Application\RetryFailedPushes\RetryFailedPushes;
use Prospecter\Core\Connectors\Application\RetryPush\RetryPush;
use Prospecter\Core\Connectors\Domain\Event\CalendarToFill;
use Prospecter\Core\Connectors\Domain\Model\CalendarEvent;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * Whether each exchange that I planned is in my calendar « Prospecter » at Google. A refused push is pushed again, and
 * my upcoming exchanges are pushed when I connect (ADR 0133). The scenarios' « elle » is the last exchange planned.
 */
final class CalendarSyncContext implements Context
{
    use SharesTheScenario;

    private const array STATES = ['en attente' => 'pending', 'synchronisée' => 'synced', 'en échec' => 'failed'];

    /** @var array{CalendarToFill, Connection}|null the current push of my upcoming exchanges: what it fills, and the connection it read */
    private ?array $pushUnderWay = null;

    #[Given('mes échanges à venir sont en cours de poussée')]
    public function myUpcomingExchangesAreBeingPushed(): void
    {
        $filled = $this->application->events->published(CalendarToFill::class);
        $connection = $this->application->connections->find(new MemberId($this->application->signedIn()), Provider::Google);
        $this->pushUnderWay = [Expect::present(array_last($filled), 'A calendar to fill was expected'), Expect::present($connection, 'A connection to Google was expected')];
    }

    /**
     * The current push writes what it did with the connection that it read when it started.
     */
    #[When('la poussée de mes échanges à venir se termine')]
    public function thePushOfMyUpcomingExchangesEnds(): void
    {
        [$filled, $connection] = $this->pushUnderWay ?? throw new \LogicException('No push of my exchanges to come is under way');
        $this->application->connections->answerTheNextReadWith($connection);
        $this->application->publishAgain($filled);
    }

    #[Given('Google ne répond pas')]
    public function googleDoesNotAnswer(): void
    {
        $this->application->calendars->stopAnswering();
    }

    #[When('Google répond de nouveau et que sa poussée est tentée de nouveau')]
    public function googleAnswersAgainAndThePushIsTriedAgain(): void
    {
        $this->application->calendars->answerAgain();
        $published = $this->application->events->published(ExchangeFactsChanged::class);
        $this->application->publishAgain(Expect::present(array_last($published), 'No fact of the exchange was published'));
    }

    #[Given('Google refuse les événements que je pousse')]
    public function googleRefusesTheEventsIPush(): void
    {
        $this->application->calendars->refuseEveryEvent();
    }

    #[When('Google accepte de nouveau mes événements et que je relance sa synchronisation')]
    public function googleAcceptsAgainAndIRetry(): void
    {
        $this->application->calendars->acceptEveryEvent();
        $this->iRetryItsPush();
    }

    #[When('je relance sa synchronisation')]
    public function iRetryItsPush(): void
    {
        $this->application->dispatch(new RetryPush($this->application->signedIn(), $this->scenario->theExchange(), $this->application->now()));
    }

    #[When('Google accepte de nouveau mes événements et que je relance toutes mes synchronisations en échec')]
    public function googleAcceptsAgainAndIRetryEveryFailedPush(): void
    {
        $this->application->calendars->acceptEveryEvent();
        $this->application->dispatch(new RetryFailedPushes($this->application->signedIn(), $this->application->now()));
    }

    #[Then('/^elle est "(?<state>[^"]+)"(?: de synchronisation)?$/')]
    public function itIs(string $state): void
    {
        Expect::same(self::STATES[$state], $this->itsSync()?->state);
    }

    #[Then('/^Google a refusé son événement le (?<day>\S+) à (?<time>\S+)$/')]
    public function googleRefusedItsEvent(string $day, string $time): void
    {
        $sync = $this->itsSync();

        Expect::same(
            ['state' => 'failed', 'reason' => 'calendarRefusedThePush', 'at' => Vocabulary::frenchMoment(sprintf('%s à %s', $day, $time))->format(\DATE_ATOM)],
            ['state' => $sync?->state, 'reason' => $sync?->reason, 'at' => $sync?->at?->format(\DATE_ATOM)],
        );
    }

    #[Then('elle n\'a pas d\'état de synchronisation')]
    public function itHasNoState(): void
    {
        Expect::same(null, $this->itsSync());
    }

    private function itsSync(): ?ExchangeSync
    {
        $kept = $this->application->consultExchangeSync($this->scenario->theExchange());
        $kept = Expect::present($kept, sprintf('Refused: %s', $this->application->refusal()?->getMessage()));

        return $kept->sync;
    }

    #[Then('/^mes connecteurs comptent (?<synced>\d+) échanges? synchronisés? et (?<failed>\d+) en échec$/')]
    public function myConnectorsCount(int $synced, int $failed): void
    {
        $connections = $this->application->consultMyConnections()->connections ?? [];
        $google = array_find($connections, static fn (MyConnection $connection): bool => 'google' === $connection->provider);

        Expect::same(['synced' => $synced, 'failed' => $failed], ['synced' => $google?->synced, 'failed' => $google?->failed]);
    }

    /**
     * « du 17/11/2026 à 14h00, du 18/11/2026 à 9h00 et du 19/11/2026 à 9h00 » are the events' moments, whatever their
     * order in Google.
     */
    #[Then('/^mon agenda Google a les événements (?<moments>du .+)$/u')]
    public function myCalendarHasTheEventsOf(string $moments): void
    {
        preg_match_all('/du (\S+) à (\S+?)(?:,|$| et)/u', $moments, $found, \PREG_SET_ORDER);
        $expected = array_map(static fn (array $moment): string => Vocabulary::frenchMoment(sprintf('%s à %s', $moment[1], $moment[2]))->format(\DATE_ATOM), $found);
        $pushed = array_values(array_map(static fn (CalendarEvent $event): string => $event->startsAt->format(\DATE_ATOM), $this->application->calendars->events()));
        sort($pushed);

        Expect::same($expected, $pushed);
    }
}
