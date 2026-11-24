<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Contact\CollectContact;

use Prospecter\Core\PersonalData\Domain\Exception\UnknownOrganization;
use Prospecter\Core\PersonalData\Domain\Model\Civility;
use Prospecter\Core\PersonalData\Domain\Model\ContactCard;
use Prospecter\Core\PersonalData\Domain\Model\NewContact;
use Prospecter\Core\PersonalData\Domain\Model\OrganizationId;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Port\ProspectedOrganizations;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Service\DuplicateSuspicion;
use Prospecter\Core\PersonalData\Domain\Service\EmailUniqueness;
use Prospecter\Core\PersonalData\Domain\Service\ReferenceUniqueness;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Contact\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Contact\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\Gesture\Origin;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Prospecter\Core\SharedKernel\Domain\Link\Designated;
use Prospecter\Core\SharedKernel\Domain\Link\ExternalReferences;

/**
 * Collects the person. The event it records attaches the person to their organizations in Prospecting
 * (ContactCollected). The handler checks every organization first, so that the attachment cannot leave the person
 * alone.
 */
final readonly class CollectContactHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private PersonRepository $persons,
        private Readers $readers,
        private ProspectedOrganizations $organizations,
        private Events $events,
        private EmailUniqueness $emails,
        private ReferenceUniqueness $references,
        private DuplicateSuspicion $duplicateSuspicion,
    ) {
    }

    public function __invoke(CollectContact $command): void
    {
        $collector = $this->readers->readerFrom(new MemberId($command->readerId));
        $organizations = array_map(static fn (string $id): OrganizationId => new OrganizationId($id), $command->organizationIds);
        $this->ensureProspected($organizations, $collector);
        $card = new ContactCard(
            name: new PersonName($command->firstName, $command->lastName),
            contactDetails: new ContactDetails(Email::orNone($command->email), PhoneNumber::orNone($command->phoneNumber), $command->language),
            civility: Civility::of($command->civility),
            jobTitle: $command->jobTitle,
            references: ExternalReferences::designating($command->links, Designated::Person),
        );
        $contact = Person::collectAsContact($collector, new NewContact(new PersonId($command->id), $card, $organizations), $command->executedAt, Origin::byAnAI($command->byAnAI));
        $this->emails->ensureAvailableFor($contact);
        $this->references->ensureAvailableFor($contact);
        $contact->reconsiderDuplicates($this->duplicateSuspicion->resemblingTo($contact, $organizations), by: $collector->id, at: $command->executedAt);
        $this->persons->save($contact);
        $this->publishWhatHappenedTo($contact);
    }

    /**
     * @param list<OrganizationId> $organizations
     */
    private function ensureProspected(array $organizations, Reader $collector): void
    {
        $prospected = $this->organizations->prospectedAmong($organizations, $collector->accountId);

        foreach ($organizations as $organization) {
            if (!array_any($prospected, $organization->equals(...))) {
                throw UnknownOrganization::identifiedBy($organization);
            }
        }
    }
}
