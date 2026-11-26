<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\ListOrganizationContacts;

use Prospecter\Core\Prospecting\Application\PersonDescription;
use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Application\Port\PersonDescriptionRetriever;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class ListOrganizationContactsHandler implements QueryHandler
{
    public function __construct(
        private OrganizationSheetRetriever $organizationSheetRetriever,
        private PersonDescriptionRetriever $personDescriptionRetriever,
    ) {
    }

    public function __invoke(ListOrganizationContacts $query, ListOrganizationContactsOutput $output): void
    {
        $organization = $this->organizationSheetRetriever->get(new OrganizationId($query->organizationId));
        $persons = $this->personDescriptionRetriever->shownTo(new MemberId($query->prospectorId), array_map(static fn (string $id): ContactId => new ContactId($id), $organization->contactIds), []);
        $contacts = array_filter(array_map(static fn (string $id): ?PersonDescription => $persons->contacts[$id] ?? null, $organization->contactIds));

        $output->present(new OrganizationContacts($organization, array_values($contacts)));
    }
}
