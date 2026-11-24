<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Duplicate\SuspectDuplicateInReturn;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * The organization that a newcomer resembles is suspected in return of being its duplicate. When the organization is
 * no longer prospected, the command has nothing left to do. When it already suspects the newcomer, it records nothing.
 */
final readonly class SuspectDuplicateInReturnHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private Events $events,
    ) {
    }

    public function __invoke(SuspectDuplicateInReturn $command): void
    {
        try {
            $organization = $this->organizations->get(new OrganizationId($command->organizationId));
        } catch (UnknownOrganization) {
            return;
        }

        $organization->suspectDuplicateOf(new OrganizationId($command->duplicateId), by: new MemberId($command->memberId), at: $command->executedAt);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }
}
