<?php

namespace App\Contacts;

use App\Contacts\Resource\OrganizationOfContactResource;
use App\Shared\Links;
use Prospecter\Core\Prospecting\Application\ConsultOrganizationsOfContacts\ConsultOrganizationsOfContacts;
use Prospecter\Core\Prospecting\Application\ConsultOrganizationsOfContacts\OrganizationOfContact;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;

/**
 * The organizations of contacts, read in Prospecting in one batch and presented with a link to each.
 */
final readonly class OrganizationsOfContacts
{
    public function __construct(
        private RefusalTranslatingHandler $consultOrganizationsOfContacts,
        private Links $links,
    ) {
    }

    /**
     * @param list<string> $contactIds
     *
     * @return array<string, list<OrganizationOfContactResource>> by contact id
     */
    public function read(string $reader, array $contactIds): array
    {
        $byContact = ($this->consultOrganizationsOfContacts)(new ConsultOrganizationsOfContacts($reader, $contactIds));

        return array_map(fn (array $organizations): array => array_map($this->present(...), $organizations), $byContact);
    }

    private function present(OrganizationOfContact $organization): OrganizationOfContactResource
    {
        return new OrganizationOfContactResource($organization->id, $organization->fullName, $this->links->to('api_organizations_show', ['id' => $organization->id]));
    }
}
