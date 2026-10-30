<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganizationsOfContacts;

use Prospecter\Core\Prospecting\Application\Port\OrganizationOfContactRetriever;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class ConsultOrganizationsOfContactsHandler implements QueryHandler
{
    public function __construct(private OrganizationOfContactRetriever $organizationOfContactRetriever, private Prospectors $prospectors)
    {
    }

    public function __invoke(ConsultOrganizationsOfContacts $query, ConsultOrganizationsOfContactsOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $contacts = array_map(static fn (string $id): PersonId => new PersonId($id), $query->personIds);

        $output->present($this->organizationOfContactRetriever->ofContacts($contacts, $prospector->accountId));
    }
}
