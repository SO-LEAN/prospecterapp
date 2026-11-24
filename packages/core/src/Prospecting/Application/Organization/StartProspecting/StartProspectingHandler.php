<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Organization\StartProspecting;

use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\WebPresence;
use Prospecter\Core\Prospecting\Domain\Model\Website;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Service\DuplicateSuspicion;
use Prospecter\Core\Prospecting\Domain\Service\EmailUniqueness;
use Prospecter\Core\Prospecting\Domain\Service\ReferenceUniqueness;
use Prospecter\Core\Prospecting\Domain\Service\WebsiteUniqueness;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\Service\GeoLocalizer;
use Prospecter\Core\SharedKernel\Domain\Company\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\Contact\Address;
use Prospecter\Core\SharedKernel\Domain\Contact\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Contact\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\Gesture\Gesture;
use Prospecter\Core\SharedKernel\Domain\Gesture\Origin;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Prospecter\Core\SharedKernel\Domain\Link\Designated;
use Prospecter\Core\SharedKernel\Domain\Link\ExternalReferences;

final readonly class StartProspectingHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private Prospectors $prospectors,
        private Events $events,
        private GeoLocalizer $geoLocalizer,
        private EmailUniqueness $emails,
        private ReferenceUniqueness $references,
        private WebsiteUniqueness $websites,
        private DuplicateSuspicion $duplicateSuspicion,
    ) {
    }

    public function __invoke(StartProspecting $command): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($command->prospectorId));
        $organization = Organization::prospectedBy(
            new Gesture($prospector->accountId, $prospector->id, $command->executedAt, Origin::byAnAI($command->byAnAI)),
            new OrganizationId($command->id),
            new LegalIdentity($command->corporateName, $command->form, $command->type),
            new ContactDetails(Email::orNone($command->email), PhoneNumber::orNone($command->phoneNumber), $command->language),
        );
        $this->placeOnTheWeb($organization, $command, $prospector->id);
        $this->locate($organization, $command, $prospector->id);
        $this->observe($organization, $command, $prospector->id);
        $this->attachToTheHolding($organization, $command, $prospector->id);
        $this->emails->ensureAvailableFor($organization);
        $this->references->ensureAvailableFor($organization);
        $this->websites->ensureAvailableFor($organization);
        $organization->reconsiderDuplicates($this->duplicateSuspicion->resemblingTo($organization), by: $prospector->id, at: $command->executedAt);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }

    /**
     * Only what is known is told. An organization started without links or a website gets no change of contact details.
     */
    private function placeOnTheWeb(Organization $organization, StartProspecting $command, MemberId $author): void
    {
        $references = ExternalReferences::designating($command->links, Designated::Organization);
        $website = Website::orNone($command->website);

        if ([] !== $references->all() || null !== $website) {
            $organization->changeContactDetails($organization->contactDetails(), new WebPresence($references, $website), $author, $command->executedAt);
        }
    }

    /**
     * Only what is known is told. An organization started without an address did not move.
     */
    private function locate(Organization $organization, StartProspecting $command, MemberId $author): void
    {
        $address = new Address($command->street, $command->postalCode, $command->city, $command->country);

        if (!$address->isEmpty()) {
            $organization->relocateTo($address, $this->geoLocalizer->locate($address), by: $author, at: $command->executedAt);
        }
    }

    private function observe(Organization $organization, StartProspecting $command, MemberId $author): void
    {
        if (null !== $command->observations) {
            $organization->writeObservations($command->observations, by: $author, at: $command->executedAt);
        }
    }

    private function attachToTheHolding(Organization $organization, StartProspecting $command, MemberId $author): void
    {
        if (null !== $command->holdingId) {
            $organization->attachToHolding($this->organizations->get(new OrganizationId($command->holdingId)), by: $author, at: $command->executedAt);
        }
    }
}
