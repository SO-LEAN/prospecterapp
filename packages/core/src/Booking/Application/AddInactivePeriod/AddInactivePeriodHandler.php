<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\AddInactivePeriod;

use Prospecter\Core\Booking\Application\BookingPages;
use Prospecter\Core\Booking\Domain\Model\InactivePeriod;
use Prospecter\Core\Booking\Domain\Model\InactivePeriodId;
use Prospecter\Core\Booking\Domain\Repository\BookingPageRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class AddInactivePeriodHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private BookingPages $bookingPages,
        private BookingPageRepository $pages,
        private Events $events,
    ) {
    }

    public function __invoke(AddInactivePeriod $command): void
    {
        $page = $this->bookingPages->of(new MemberId($command->memberId), $command->executedAt);
        $page->addInactivePeriod(new InactivePeriod(new InactivePeriodId($command->periodId), $command->firstDay, $command->lastDay), $command->executedAt);
        $this->pages->save($page);
        $this->publishWhatHappenedTo($page);
    }
}
