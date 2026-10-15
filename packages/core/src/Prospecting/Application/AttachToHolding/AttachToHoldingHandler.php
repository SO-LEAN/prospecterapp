<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\AttachToHolding;

use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospectors;
use Prospecter\Core\SharedKernel\Application\CommandHandler;

final readonly class AttachToHoldingHandler implements CommandHandler
{
    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors)
    {
    }

    public function __invoke(AttachToHolding $command): void
    {
        $prospector = $this->prospectors->get($command->prospectorId);
        $organization = $this->organizations->get($command->id);
        $organization->attachToHolding($this->organizations->get($command->holdingId), by: $prospector);
        $this->organizations->save($organization);
    }
}
