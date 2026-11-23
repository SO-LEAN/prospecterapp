<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\NameOrganizationsOfContacts;

use Prospecter\Core\Prospecting\Application\Port\OrganizationOfContactRetriever;
use Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts\NameOrganizationsOfContacts;
use Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts\NameOrganizationsOfContactsOutput;
use Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts\NameOrganizationsOfContactsService;
use Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts\OrganizationsOfContactResponse;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;

final readonly class NameOrganizationsOfContactsHandler implements QueryHandler, NameOrganizationsOfContactsService
{
    public function __construct(private OrganizationOfContactRetriever $organizationOfContactRetriever)
    {
    }

    public function __invoke(NameOrganizationsOfContacts $query, NameOrganizationsOfContactsOutput $output): void
    {
        $contacts = array_map(static fn (string $id): ContactId => new ContactId($id), $query->personIds);
        $named = array_filter($this->organizationOfContactRetriever->ofContacts($contacts, new AccountId($query->accountId)));

        $output->present(array_map(
            static fn (string $personId, array $organizations): OrganizationsOfContactResponse => new OrganizationsOfContactResponse($personId, array_column($organizations, 'fullName')),
            array_keys($named),
            $named,
        ));
    }
}
