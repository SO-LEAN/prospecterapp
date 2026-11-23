<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\OpenBooking;

use Prospecter\Core\Booking\Application\BookingPages;
use Prospecter\Core\Booking\Domain\Model\BookingCode;
use Prospecter\Core\Booking\Domain\Port\ConnectedCalendars;
use Prospecter\Core\Booking\Domain\Repository\BookingPageRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class OpenBookingHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private BookingPages $bookingPages,
        private BookingPageRepository $pages,
        private ConnectedCalendars $calendars,
        private Events $events,
    ) {
    }

    public function __invoke(OpenBooking $command): void
    {
        $host = new MemberId($command->memberId);
        $page = $this->bookingPages->of($host, $command->executedAt);
        $page->open(new BookingCode($command->drawnCode), $this->calendars->isConnectedFor($host), $command->executedAt);
        $this->pages->save($page);
        $this->publishWhatHappenedTo($page);
    }
}
