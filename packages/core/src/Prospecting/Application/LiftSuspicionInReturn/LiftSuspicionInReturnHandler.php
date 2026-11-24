<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\LiftSuspicionInReturn;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * When an organization no longer resembles another one, the other one is no longer suspected of being its duplicate
 * in return. When the other one is no longer prospected, the command has nothing left to do. When it no longer
 * suspects the first one, it records nothing.
 */
final readonly class LiftSuspicionInReturnHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private Events $events,
    ) {
    }

    public function __invoke(LiftSuspicionInReturn $command): void
    {
        try {
            $organization = $this->organizations->get(new OrganizationId($command->organizationId));
        } catch (UnknownOrganization) {
            return;
        }

        $organization->liftSuspicionOf(new OrganizationId($command->duplicateId), by: new MemberId($command->memberId), at: $command->executedAt);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }
}
