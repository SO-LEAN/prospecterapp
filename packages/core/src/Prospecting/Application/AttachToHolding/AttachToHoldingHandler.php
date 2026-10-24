<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\AttachToHolding;

use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

final readonly class AttachToHoldingHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(private OrganizationRepository $organizations, private Events $events)
    {
    }

    public function __invoke(AttachToHolding $command): void
    {
        $author = new MemberId($command->prospectorId);
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $organization->attachToHolding($this->organizations->get(new OrganizationId($command->holdingId)), by: $author, at: $command->executedAt);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }
}
