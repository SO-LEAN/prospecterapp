<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\StopProspecting;

use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\OrphanContacts;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\Prospecting\Domain\Prospectors;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

final readonly class StopProspectingHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private Prospectors $prospectors,
        private Events $events,
        private OrphanContacts $orphans,
    ) {
    }

    public function __invoke(StopProspecting $command): void
    {
        $prospector = $this->prospectors->get(new MemberId($command->prospectorId));
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $organization->stopBeingProspected(by: $prospector, at: $command->executedAt, orphans: $this->orphans);
        $this->detachTheSubsidiariesOf($organization, $prospector, $command->executedAt);
        $this->organizations->remove($organization);
        $this->publishWhatHappenedTo($organization);
    }

    private function detachTheSubsidiariesOf(Organization $holding, Prospector $prospector, \DateTimeImmutable $at): void
    {
        foreach ($this->organizations->subsidiariesOf($holding->id()) as $subsidiary) {
            $subsidiary->detachFromHolding(by: $prospector, at: $at);
            $this->organizations->save($subsidiary);
            $this->publishWhatHappenedTo($subsidiary);
        }
    }
}
