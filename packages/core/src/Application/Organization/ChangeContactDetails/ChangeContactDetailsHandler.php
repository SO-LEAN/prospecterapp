<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization\ChangeContactDetails;

use Prospecter\Core\Application\CommandHandler;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final readonly class ChangeContactDetailsHandler implements CommandHandler
{
    public function __construct(private OrganizationRepository $organizations, private UserRepository $users)
    {
    }

    public function __invoke(ChangeContactDetails $command): void
    {
        $prospector = $this->users->get($command->prospectorId);
        $organization = $this->organizations->get($command->id);
        $organization->changeContactDetails($command->contactDetails, by: $prospector);
        $this->organizations->save($organization);
    }
}
