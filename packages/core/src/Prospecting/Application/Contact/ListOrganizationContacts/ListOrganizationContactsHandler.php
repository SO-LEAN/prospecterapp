<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\ListOrganizationContacts;

use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Port\KnownContacts;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Erasing a contact forgets them in PersonalData at once, and in their organizations once the reaction follows. Until
 * then, they are no longer among the organization's contacts.
 */
final readonly class ListOrganizationContactsHandler implements QueryHandler
{
    public function __construct(
        private OrganizationSheetRetriever $organizationSheetRetriever,
        private KnownContacts $knownContacts,
    ) {
    }

    public function __invoke(ListOrganizationContacts $query, ListOrganizationContactsOutput $output): void
    {
        $organization = $this->organizationSheetRetriever->get(new OrganizationId($query->organizationId));
        $contacts = $this->knownContacts->whichAreContactsOf(array_map(static fn (string $id): ContactId => new ContactId($id), $organization->contactIds), new AccountId($organization->accountId));

        $output->present(new OrganizationContacts($organization, array_map(static fn (ContactId $contact): string => $contact->value, $contacts)));
    }
}
