<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Duplicate\DismissSuspicionInReturn;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * A member stated that an organization is not a duplicate of another. In return, the other organization is not a
 * duplicate of the first, by the same member. When the account stopped prospecting it meanwhile, the command has
 * nothing left to do. When the organization already knows it, it records nothing.
 */
final readonly class DismissSuspicionInReturnHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private Events $events,
    ) {
    }

    public function __invoke(DismissSuspicionInReturn $command): void
    {
        try {
            $organization = $this->organizations->get(new OrganizationId($command->organizationId));
        } catch (UnknownOrganization) {
            return;
        }

        $organization->knowItIsNoDuplicateOf(new OrganizationId($command->duplicateId), by: new MemberId($command->memberId), at: $command->executedAt);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }
}
