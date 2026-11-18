<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\SetWeeklyWindows;

use Prospecter\Core\Booking\Application\BookingPages;
use Prospecter\Core\Booking\Domain\Model\HostTimeZone;
use Prospecter\Core\Booking\Domain\Model\WallClockTime;
use Prospecter\Core\Booking\Domain\Model\Weekday;
use Prospecter\Core\Booking\Domain\Model\WeeklyWindows;
use Prospecter\Core\Booking\Domain\Model\Window;
use Prospecter\Core\Booking\Domain\Repository\BookingPageRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class SetWeeklyWindowsHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private BookingPages $bookingPages,
        private BookingPageRepository $pages,
        private Events $events,
    ) {
    }

    public function __invoke(SetWeeklyWindows $command): void
    {
        $page = $this->bookingPages->of(new MemberId($command->memberId), $command->executedAt);
        $page->setWindows(new WeeklyWindows(...array_map(self::window(...), $command->windows)), new HostTimeZone($command->timeZone), $command->executedAt);
        $this->pages->save($page);
        $this->publishWhatHappenedTo($page);
    }

    /**
     * @param array{weekday: string, from: string, to: string} $window
     */
    private static function window(array $window): Window
    {
        $weekday = Weekday::tryFrom($window['weekday']) ?? throw BusinessRuleViolation::because('A window is on a day of the week', 'windows', context: ['weekday' => $window['weekday']]);

        return new Window($weekday, WallClockTime::at($window['from'], 'windows'), WallClockTime::at($window['to'], 'windows'));
    }
}
