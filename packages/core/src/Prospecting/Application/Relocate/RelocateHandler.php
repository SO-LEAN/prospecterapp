<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Relocate;

use Prospecter\Core\Prospecting\Application\GeoLocalizer;
use Prospecter\Core\Prospecting\Application\Port\GeoLocation;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospectors;
use Prospecter\Core\SharedKernel\Application\CommandHandler;

final readonly class RelocateHandler implements CommandHandler
{
    private GeoLocalizer $geoLocalizer;

    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors, GeoLocation $geoLocation)
    {
        $this->geoLocalizer = new GeoLocalizer($geoLocation);
    }

    public function __invoke(Relocate $command): void
    {
        $prospector = $this->prospectors->get($command->prospectorId);
        $organization = $this->organizations->get($command->id);
        $organization->relocateTo($command->address, $this->geoLocalizer->locate($command->address), by: $prospector);
        $this->organizations->save($organization);
    }
}
