<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\StartProspecting;

use Prospecter\Core\Prospecting\Application\GeoLocalizer;
use Prospecter\Core\Prospecting\Application\Port\GeoLocation;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospectors;
use Prospecter\Core\SharedKernel\Application\CommandHandler;

final readonly class StartProspectingHandler implements CommandHandler
{
    private GeoLocalizer $geoLocalizer;

    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors, GeoLocation $geoLocation)
    {
        $this->geoLocalizer = new GeoLocalizer($geoLocation);
    }

    public function __invoke(StartProspecting $command): void
    {
        $prospector = $this->prospectors->get($command->prospectorId);
        $organization = Organization::prospectedBy($prospector, $command->id, $command->legalIdentity, $command->contactDetails);
        $organization->relocateTo($command->address, $this->geoLocalizer->locate($command->address), by: $prospector);
        $organization->writeObservations($command->observations, by: $prospector);

        if (null !== $command->holdingId) {
            $organization->attachToHolding($this->organizations->get($command->holdingId), by: $prospector);
        }

        $this->organizations->save($organization);
    }
}
