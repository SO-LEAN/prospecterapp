<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganizationsOfContacts;

use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class ConsultOrganizationsOfContactsHandler implements QueryHandler
{
    public function __construct(private OrganizationRepository $organizations, private ProspectorService $prospectorService)
    {
    }

    public function __invoke(ConsultOrganizationsOfContacts $query, ConsultOrganizationsOfContactsOutput $output): void
    {
        $prospector = $this->prospectorService->prospectorFrom(new MemberId($query->prospectorId));
        $contacts = array_map(static fn (string $id): PersonId => new PersonId($id), $query->personIds);
        $organizations = $this->organizations->withContacts($contacts, $prospector->accountId);
        $byContact = [];

        foreach ($contacts as $contact) {
            $byContact[$contact->value] = array_map(OrganizationOfContact::of(...), array_values(array_filter($organizations, static fn (Organization $organization): bool => $organization->hasContact($contact))));
        }

        $output->present($byContact);
    }
}
