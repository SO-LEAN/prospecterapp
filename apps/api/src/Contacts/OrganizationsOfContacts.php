<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\OrganizationOfContactResource;
use App\Shared\Links;
use Prospecter\Core\Prospecting\Application\Contact\ConsultOrganizationsOfContacts\ConsultOrganizationsOfContacts;
use Prospecter\Core\Prospecting\Application\Contact\ConsultOrganizationsOfContacts\KeptOrganizationsOfContacts;
use Prospecter\Core\Prospecting\Application\Contact\ConsultOrganizationsOfContacts\OrganizationOfContact;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Reads the contacts' organizations in Prospecting, in one batch, and presents each one with a link to it.
 */
final readonly class OrganizationsOfContacts
{
    public function __construct(
        private QueryHandler $consultOrganizationsOfContacts,
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
        $kept = new KeptOrganizationsOfContacts();
        $this->consultOrganizationsOfContacts->__invoke(new ConsultOrganizationsOfContacts($reader, $contactIds), $kept);

        return array_map(fn (array $organizations): array => array_map($this->resourceOf(...), $organizations), $kept->organizations);
    }

    private function resourceOf(OrganizationOfContact $organization): OrganizationOfContactResource
    {
        return OrganizationOfContactResource::of($organization, $this->links);
    }
}
