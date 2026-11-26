<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\OrganizationOfContactResource;
use App\Contacts\Resource\OrganizationsOfContactResource;
use App\Shared\Links;
use Prospecter\Core\Prospecting\Application\Contact\ConsultOrganizationsOfContacts\ConsultOrganizationsOfContactsOutput;
use Prospecter\Core\Prospecting\Application\Contact\ConsultOrganizationsOfContacts\OrganizationOfContact;

/**
 * Presents one contact's organizations, each with a link to it (ADR 0085). A contact the reader does not reach has
 * none.
 */
final class OrganizationsOfContactPresenter implements ConsultOrganizationsOfContactsOutput
{
    public private(set) OrganizationsOfContactResource $resource;

    public function __construct(private readonly string $contactId, private readonly Links $links)
    {
    }

    public function present(array $organizations): void
    {
        $this->resource = new OrganizationsOfContactResource(array_map(fn (OrganizationOfContact $organization): OrganizationOfContactResource => OrganizationOfContactResource::of($organization, $this->links), $organizations[$this->contactId] ?? []));
    }
}
