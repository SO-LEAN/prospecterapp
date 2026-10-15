<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account\ReviseMyOrganizationIdentity;

use Prospecter\Core\Application\CommandHandler;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final readonly class ReviseMyOrganizationIdentityHandler implements CommandHandler
{
    public function __construct(private OrganizationRepository $organizations, private UserRepository $users)
    {
    }

    public function __invoke(ReviseMyOrganizationIdentity $command): void
    {
        $me = $this->users->get($command->userId);
        $myOrganization = $this->organizations->get($me->organizationId());
        $myOrganization->reviseLegalIdentity($command->legalIdentity, by: $me);
        $this->organizations->save($myOrganization);
    }
}
