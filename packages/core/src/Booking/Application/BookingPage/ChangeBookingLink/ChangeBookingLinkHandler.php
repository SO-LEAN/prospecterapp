<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\BookingPage\ChangeBookingLink;

use Prospecter\Core\Booking\Application\BookingPage\BookingPages;
use Prospecter\Core\Booking\Domain\Model\BookingCode;
use Prospecter\Core\Booking\Domain\Repository\BookingPageRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class ChangeBookingLinkHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private BookingPages $bookingPages,
        private BookingPageRepository $pages,
        private Events $events,
    ) {
    }

    public function __invoke(ChangeBookingLink $command): void
    {
        $page = $this->bookingPages->of(new MemberId($command->memberId), $command->executedAt);
        $page->changeLink(new BookingCode($command->drawnCode), $command->executedAt);
        $this->pages->save($page);
        $this->publishWhatHappenedTo($page);
    }
}
