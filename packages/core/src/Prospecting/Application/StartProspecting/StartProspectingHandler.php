<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\StartProspecting;

use Prospecter\Core\Prospecting\Domain\Model\LinkedInPage;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\Website;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Service\EmailUniqueness;
use Prospecter\Core\Prospecting\Domain\Service\LinkedInPageUniqueness;
use Prospecter\Core\Prospecting\Domain\Service\WebsiteUniqueness;
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
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

final readonly class StartProspectingHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private Prospectors $prospectors,
        private Events $events,
        private GeoLocalizer $geoLocalizer,
        private EmailUniqueness $emails,
        private LinkedInPageUniqueness $linkedInPages,
        private WebsiteUniqueness $websites,
    ) {
    }

    public function __invoke(StartProspecting $command): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($command->prospectorId));
        $organization = Organization::prospectedBy(
            $prospector,
            new OrganizationId($command->id),
            new LegalIdentity($command->corporateName, $command->form, $command->type),
            new ContactDetails(Email::orNone($command->email), PhoneNumber::orNone($command->phoneNumber), $command->language),
            $command->executedAt,
        );
        $this->placeOnTheWeb($organization, $command, $prospector->id);
        $this->locate($organization, $command, $prospector->id);
        $this->observe($organization, $command, $prospector->id);
        $this->attachToTheHolding($organization, $command, $prospector->id);
        $this->emails->ensureAvailableFor($organization);
        $this->linkedInPages->ensureAvailableFor($organization);
        $this->websites->ensureAvailableFor($organization);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }

    /**
     * Only what is known is told: an organization started without its page nor its website has no contact details
     * changed.
     */
    private function placeOnTheWeb(Organization $organization, StartProspecting $command, MemberId $author): void
    {
        $linkedInPage = LinkedInPage::orNone($command->linkedInPage);
        $website = Website::orNone($command->website);

        if (null !== $linkedInPage || null !== $website) {
            $organization->changeContactDetails($organization->contactDetails(), $linkedInPage, $website, by: $author, at: $command->executedAt);
        }
    }

    /**
     * Only what is known is told: an organization started without an address did not move.
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
