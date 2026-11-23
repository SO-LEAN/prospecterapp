<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\ConsultPublicBookingPage;

use Prospecter\Core\Booking\Application\Port\PublicBookingPageRetriever;
use Prospecter\Core\Booking\Application\PublicBookingPage;
use Prospecter\Core\Booking\Application\PublicHost;
use Prospecter\Core\Booking\Domain\Model\Availability;
use Prospecter\Core\Booking\Domain\Model\BookableDay;
use Prospecter\Core\Booking\Domain\Model\HostTimeZone;
use Prospecter\Core\Booking\Domain\Model\InactivePeriod;
use Prospecter\Core\Booking\Domain\Model\InactivePeriodId;
use Prospecter\Core\Booking\Domain\Model\WallClockTime;
use Prospecter\Core\Booking\Domain\Model\Weekday;
use Prospecter\Core\Booking\Domain\Model\WeeklyWindows;
use Prospecter\Core\Booking\Domain\Model\Window;
use Prospecter\Core\Booking\Domain\Port\Hosts;
use Prospecter\Core\Booking\Domain\Rule\FreeSlots;
use Prospecter\Core\Booking\Domain\Service\BusyPeriods;
use Prospecter\Core\Booking\Domain\Service\LinkAvailability;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class ConsultPublicBookingPageHandler implements QueryHandler
{
    /**
     * A page whose windows were never set has no clock yet. It has no slot, whatever the clock.
     */
    private const string NO_CLOCK_YET = 'UTC';

    public function __construct(
        private PublicBookingPageRetriever $pages,
        private BusyPeriods $busyPeriods,
        private LinkAvailability $links,
        private Hosts $hosts,
    ) {
    }

    public function __invoke(ConsultPublicBookingPage $query, ConsultPublicBookingPageOutput $output): void
    {
        $schedule = $this->pages->byCode($query->code);
        if (null === $schedule || !$this->links->offersSlots($schedule->open, new MemberId($schedule->host))) {
            $output->present(PublicBookingPage::unavailable());

            return;
        }

        $output->present(new PublicBookingPage(true, $this->hostOf($schedule), $schedule->channels, $schedule->timeZone, $this->freeDaysOf($schedule, $query->at)));
    }

    private function hostOf(BookingSchedule $schedule): PublicHost
    {
        $identity = $this->hosts->identityOf(new MemberId($schedule->host));

        return new PublicHost($identity->firstName, $identity->lastName, $identity->pictureUrl, $identity->organization);
    }

    /**
     * @return list<array{day: string, slots: list<string>}>
     */
    private function freeDaysOf(BookingSchedule $schedule, \DateTimeImmutable $at): array
    {
        $busyPeriods = $this->busyPeriods->of(new AccountId($schedule->accountId), new MemberId($schedule->host), $at);
        $days = FreeSlots::given(self::availabilityOf($schedule), $busyPeriods, $at);

        return array_map(static fn (BookableDay $day): array => ['day' => $day->day, 'slots' => array_map(static fn (\DateTimeImmutable $slot): string => $slot->format(\DATE_ATOM), $day->slots)], $days);
    }

    private static function availabilityOf(BookingSchedule $schedule): Availability
    {
        return new Availability(
            new WeeklyWindows(...array_map(static fn (array $window): Window => new Window(Weekday::from($window['weekday']), WallClockTime::at($window['from'], 'from'), WallClockTime::at($window['to'], 'to')), $schedule->windows)),
            new HostTimeZone($schedule->timeZone ?? self::NO_CLOCK_YET),
            array_map(static fn (array $period): InactivePeriod => new InactivePeriod(new InactivePeriodId($period['id']), $period['firstDay'], $period['lastDay']), $schedule->inactivePeriods),
        );
    }
}
