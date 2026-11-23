<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\RemoveInactivePeriod;

use Prospecter\Core\Booking\Domain\Exception\UnknownInactivePeriod;
use Prospecter\Core\Booking\Domain\Model\InactivePeriodId;
use Prospecter\Core\Booking\Domain\Repository\BookingPageRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class RemoveInactivePeriodHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private BookingPageRepository $pages,
        private Events $events,
    ) {
    }

    /**
     * A page that was never set up has no period to remove.
     */
    public function __invoke(RemoveInactivePeriod $command): void
    {
        $period = new InactivePeriodId($command->periodId);
        $page = $this->pages->find(new MemberId($command->memberId)) ?? throw UnknownInactivePeriod::identifiedBy($period);
        $page->removeInactivePeriod($period, $command->executedAt);
        $this->pages->save($page);
        $this->publishWhatHappenedTo($page);
    }
}
