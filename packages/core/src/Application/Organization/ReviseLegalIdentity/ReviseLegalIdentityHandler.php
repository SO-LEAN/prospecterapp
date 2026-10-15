<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization\ReviseLegalIdentity;

use Prospecter\Core\Application\CommandHandler;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final readonly class ReviseLegalIdentityHandler implements CommandHandler
{
    public function __construct(private OrganizationRepository $organizations, private UserRepository $users)
    {
    }

    public function __invoke(ReviseLegalIdentity $command): void
    {
        $prospector = $this->users->get($command->prospectorId);
        $organization = $this->organizations->get($command->id);
        $organization->reviseLegalIdentity($command->legalIdentity, by: $prospector);
        $this->organizations->save($organization);
    }
}
