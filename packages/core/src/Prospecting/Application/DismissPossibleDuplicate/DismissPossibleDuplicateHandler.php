<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DismissPossibleDuplicate;

use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class DismissPossibleDuplicateHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private Events $events,
    ) {
    }

    public function __invoke(DismissPossibleDuplicate $command): void
    {
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $organization->dismissSuspicionOf(new OrganizationId($command->duplicateId), by: new MemberId($command->prospectorId), at: $command->executedAt);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }
}
