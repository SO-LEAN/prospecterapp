<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization\Relocate;

use Prospecter\Core\Application\CommandHandler;
use Prospecter\Core\Application\Port\GeoLocation;
use Prospecter\Core\Application\Service\GeoLocalizer;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final readonly class RelocateHandler implements CommandHandler
{
    private GeoLocalizer $geoLocalizer;

    public function __construct(private OrganizationRepository $organizations, private UserRepository $users, GeoLocation $geoLocation)
    {
        $this->geoLocalizer = new GeoLocalizer($geoLocation);
    }

    public function __invoke(Relocate $command): void
    {
        $prospector = $this->users->get($command->prospectorId);
        $organization = $this->organizations->get($command->id);
        $organization->relocateTo($command->address, $this->geoLocalizer->locate($command->address), by: $prospector);
        $this->organizations->save($organization);
    }
}
