<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization\DetachFromHolding;

use Prospecter\Core\Application\CommandHandler;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final readonly class DetachFromHoldingHandler implements CommandHandler
{
    public function __construct(private OrganizationRepository $organizations, private UserRepository $users)
    {
    }

    public function __invoke(DetachFromHolding $command): void
    {
        $prospector = $this->users->get($command->prospectorId);
        $organization = $this->organizations->get($command->id);
        $organization->detachFromHolding(by: $prospector);
        $this->organizations->save($organization);
    }
}
