<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\AttachToHolding;

use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospectors;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

final readonly class AttachToHoldingHandler implements CommandHandler
{
    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors)
    {
    }

    public function __invoke(AttachToHolding $command): void
    {
        $prospector = $this->prospectors->get(new MemberId($command->prospectorId));
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $organization->attachToHolding($this->organizations->get(new OrganizationId($command->holdingId)), by: $prospector, at: $command->executedAt);
        $this->organizations->save($organization);
    }
}
