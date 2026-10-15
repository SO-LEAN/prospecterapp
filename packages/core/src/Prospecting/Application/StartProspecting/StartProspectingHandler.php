<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\StartProspecting;

use Prospecter\Core\Prospecting\Application\GeoLocalizer;
use Prospecter\Core\Prospecting\Application\Port\GeoLocation;
use Prospecter\Core\Prospecting\Domain\Address;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\Prospecting\Domain\Prospectors;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class StartProspectingHandler implements CommandHandler
{
    private GeoLocalizer $geoLocalizer;

    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors, GeoLocation $geoLocation)
    {
        $this->geoLocalizer = new GeoLocalizer($geoLocation);
    }

    public function __invoke(StartProspecting $command): void
    {
        $prospector = $this->prospectors->get(new MemberId($command->prospectorId));
        $organization = Organization::prospectedBy(
            $prospector,
            new OrganizationId($command->id),
            new LegalIdentity($command->corporateName, $command->form, $command->type),
            new ContactDetails(Email::orNone($command->email), $command->phoneNumber, $command->language),
            $command->executedAt,
        );
        $address = new Address($command->street, $command->postalCode, $command->city, $command->country);
        $organization->relocateTo($address, $this->geoLocalizer->locate($address), by: $prospector, at: $command->executedAt);
        $organization->writeObservations($command->observations, by: $prospector, at: $command->executedAt);
        $this->attachToTheHolding($organization, $command, $prospector);
        $this->organizations->save($organization);
    }

    private function attachToTheHolding(Organization $organization, StartProspecting $command, Prospector $prospector): void
    {
        if (null !== $command->holdingId) {
            $organization->attachToHolding($this->organizations->get(new OrganizationId($command->holdingId)), by: $prospector, at: $command->executedAt);
        }
    }
}
