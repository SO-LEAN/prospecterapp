<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Relocate;

use Prospecter\Core\Prospecting\Application\GeoLocalizer;
use Prospecter\Core\Prospecting\Application\Port\GeoLocation;
use Prospecter\Core\Prospecting\Domain\Address;
use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospectors;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class RelocateHandler implements CommandHandler
{
    private GeoLocalizer $geoLocalizer;

    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors, GeoLocation $geoLocation)
    {
        $this->geoLocalizer = new GeoLocalizer($geoLocation);
    }

    public function __invoke(Relocate $command): void
    {
        $prospector = $this->prospectors->get(new MemberId($command->prospectorId));
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $address = new Address($command->street, $command->postalCode, $command->city, $command->country);
        $organization->relocateTo($address, $this->geoLocalizer->locate($address), by: $prospector, at: $command->executedAt);
        $this->organizations->save($organization);
    }
}
