<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\CollectContact;

use Prospecter\Core\PersonalData\Domain\EmailUniqueness;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownOrganization;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonName;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\ProspectedOrganizations;
use Prospecter\Core\PersonalData\Domain\Reader;
use Prospecter\Core\PersonalData\Domain\Readers;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Collects the person; the event it records attaches it to its organizations in Prospecting (ContactCollected).
 * Every organization is checked first, so that the attachment cannot leave the person alone.
 */
final readonly class CollectContactHandler implements CommandHandler
{
    private EmailUniqueness $emails;

    public function __construct(
        private PersonRepository $persons,
        private Readers $readers,
        private ProspectedOrganizations $organizations,
        private Events $events,
    ) {
        $this->emails = new EmailUniqueness($persons);
    }

    public function __invoke(CollectContact $command): void
    {
        $collector = $this->readers->get(new MemberId($command->readerId));
        $organizations = array_map(static fn (string $id): OrganizationId => new OrganizationId($id), $command->organizationIds);
        $this->ensureProspected($organizations, $collector);
        $contact = Person::collectAsContact(
            by: $collector,
            id: new PersonId($command->id),
            name: new PersonName($command->firstName, $command->lastName),
            jobTitle: $command->jobTitle,
            contactDetails: new ContactDetails(Email::orNone($command->email), $command->phoneNumber, $command->language),
            organizations: $organizations,
            at: $command->executedAt,
        );
        $this->emails->ensureAvailableFor($contact);
        $this->persons->save($contact);
        $this->events->publish(...$contact->releaseEvents());
    }

    /**
     * @param list<OrganizationId> $organizations
     */
    private function ensureProspected(array $organizations, Reader $collector): void
    {
        foreach ($organizations as $organization) {
            if (!$this->organizations->isProspectedBy($organization, $collector->accountId)) {
                throw UnknownOrganization::identifiedBy($organization);
            }
        }
    }
}
