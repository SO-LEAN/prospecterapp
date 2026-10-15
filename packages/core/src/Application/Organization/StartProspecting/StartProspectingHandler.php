<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization\StartProspecting;

use Prospecter\Core\Application\CommandHandler;
use Prospecter\Core\Application\Port\GeoLocation;
use Prospecter\Core\Application\Service\GeoLocalizer;
use Prospecter\Core\Domain\Organization\Organization;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final readonly class StartProspectingHandler implements CommandHandler
{
    private GeoLocalizer $geoLocalizer;

    public function __construct(private OrganizationRepository $organizations, private UserRepository $users, GeoLocation $geoLocation)
    {
        $this->geoLocalizer = new GeoLocalizer($geoLocation);
    }

    public function __invoke(StartProspecting $command): void
    {
        $prospector = $this->users->get($command->prospectorId);
        $organization = Organization::prospectedBy($prospector, $command->id, $command->legalIdentity, $command->contactDetails);
        $organization->relocateTo($command->address, $this->geoLocalizer->locate($command->address), by: $prospector);
        $organization->writeObservations($command->observations, by: $prospector);

        if (null !== $command->holdingId) {
            $organization->attachToHolding($this->organizations->get($command->holdingId), by: $prospector);
        }

        $this->organizations->save($organization);
    }
}
