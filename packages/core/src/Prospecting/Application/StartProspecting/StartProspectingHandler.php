<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\StartProspecting;

use Prospecter\Core\Prospecting\Domain\EmailUniqueness;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\GeoLocalizer;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

final readonly class StartProspectingHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private ProspectorService $prospectorService,
        private Events $events,
        private GeoLocalizer $geoLocalizer,
        private EmailUniqueness $emails,
    ) {
    }

    public function __invoke(StartProspecting $command): void
    {
        $prospector = $this->prospectorService->prospectorFrom(new MemberId($command->prospectorId));
        $organization = Organization::prospectedBy(
            $prospector,
            new OrganizationId($command->id),
            new LegalIdentity($command->corporateName, $command->form, $command->type),
            new ContactDetails(Email::orNone($command->email), $command->phoneNumber, $command->language),
            $command->executedAt,
        );
        $this->locate($organization, $command, $prospector);
        $this->observe($organization, $command, $prospector);
        $this->attachToTheHolding($organization, $command, $prospector);
        $this->emails->ensureAvailableFor($organization);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }

    /**
     * Only what is known is told: an organization started without an address did not move.
     */
    private function locate(Organization $organization, StartProspecting $command, Prospector $prospector): void
    {
        $address = new Address($command->street, $command->postalCode, $command->city, $command->country);

        if (!$address->isEmpty()) {
            $organization->relocateTo($address, $this->geoLocalizer->locate($address), by: $prospector, at: $command->executedAt);
        }
    }

    private function observe(Organization $organization, StartProspecting $command, Prospector $prospector): void
    {
        if (null !== $command->observations) {
            $organization->writeObservations($command->observations, by: $prospector, at: $command->executedAt);
        }
    }

    private function attachToTheHolding(Organization $organization, StartProspecting $command, Prospector $prospector): void
    {
        if (null !== $command->holdingId) {
            $organization->attachToHolding($this->organizations->get(new OrganizationId($command->holdingId)), by: $prospector, at: $command->executedAt);
        }
    }
}
