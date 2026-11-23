<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\WhichOrganizationsNameTheseContacts;

use Prospecter\Core\Prospecting\Application\Port\OrganizationOfContactRetriever;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsNameTheseContacts\OrganizationsNamingContactResponse;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsNameTheseContacts\WhichOrganizationsNameTheseContacts;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsNameTheseContacts\WhichOrganizationsNameTheseContactsOutput;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsNameTheseContacts\WhichOrganizationsNameTheseContactsService;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;

final readonly class WhichOrganizationsNameTheseContactsHandler implements QueryHandler, WhichOrganizationsNameTheseContactsService
{
    public function __construct(private OrganizationOfContactRetriever $organizationOfContactRetriever)
    {
    }

    public function __invoke(WhichOrganizationsNameTheseContacts $query, WhichOrganizationsNameTheseContactsOutput $output): void
    {
        $contacts = array_map(static fn (string $id): ContactId => new ContactId($id), $query->personIds);
        $named = array_filter($this->organizationOfContactRetriever->ofContacts($contacts, new AccountId($query->accountId)));

        $output->present(array_map(
            static fn (string $personId, array $organizations): OrganizationsNamingContactResponse => new OrganizationsNamingContactResponse($personId, array_column($organizations, 'id')),
            array_keys($named),
            $named,
        ));
    }
}
