<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganizationsOfContacts;

use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class ConsultOrganizationsOfContactsHandler implements QueryHandler
{
    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors)
    {
    }

    public function __invoke(ConsultOrganizationsOfContacts $query, ConsultOrganizationsOfContactsOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $contacts = array_map(static fn (string $id): PersonId => new PersonId($id), $query->personIds);
        $organizationsOfContacts = $this->organizations->withContacts($contacts, $prospector->accountId);
        $byContact = [];

        foreach ($contacts as $contact) {
            $byContact[$contact->value] = array_map(OrganizationOfContact::of(...), array_values(array_filter($organizationsOfContacts, static fn (Organization $organization): bool => $organization->hasContact($contact))));
        }

        $output->present($byContact);
    }
}
