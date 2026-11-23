<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\CloseBooking;

use Prospecter\Core\Booking\Application\BookingPages;
use Prospecter\Core\Booking\Domain\Repository\BookingPageRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class CloseBookingHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private BookingPages $bookingPages,
        private BookingPageRepository $pages,
        private Events $events,
    ) {
    }

    public function __invoke(CloseBooking $command): void
    {
        $page = $this->bookingPages->of(new MemberId($command->memberId), $command->executedAt);
        $page->close($command->executedAt);
        $this->pages->save($page);
        $this->publishWhatHappenedTo($page);
    }
}
