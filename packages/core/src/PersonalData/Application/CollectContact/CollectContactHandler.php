<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\CollectContact;

use Prospecter\Core\PersonalData\Domain\Exception\UnknownOrganization;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Port\ProspectedOrganizations;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Service\EmailUniqueness;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * Collects the person; the event it records attaches it to its organizations in Prospecting (ContactCollected).
 * Every organization is checked first, so that the attachment cannot leave the person alone.
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
    ) {
    }

    public function __invoke(CollectContact $command): void
    {
        $collector = $this->readers->readerFrom(new MemberId($command->readerId));
        $organizations = array_map(static fn (string $id): OrganizationId => new OrganizationId($id), $command->organizationIds);
        $this->ensureProspected($organizations, $collector);
        $contact = Person::collectAsContact(
            by: $collector,
            id: new PersonId($command->id),
            name: new PersonName($command->firstName, $command->lastName),
            jobTitle: $command->jobTitle,
            contactDetails: new ContactDetails(Email::orNone($command->email), PhoneNumber::orNone($command->phoneNumber), $command->language),
            organizations: $organizations,
            at: $command->executedAt,
        );
        $this->emails->ensureAvailableFor($contact);
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
