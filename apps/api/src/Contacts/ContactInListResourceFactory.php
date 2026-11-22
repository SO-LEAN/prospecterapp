<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\ContactInListResource;
use App\Contacts\Resource\OrganizationOfContactResource;
use App\PersonalData\Resource\PersonContactDetailsResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Shared\Links;
use Prospecter\Core\PersonalData\Application\PersonCard;

/**
 * A contact in a list, with the organizations naming them and the link to their card.
 */
final readonly class ContactInListResourceFactory
{
    public function __construct(private Links $links)
    {
    }

    /**
     * @param list<OrganizationOfContactResource> $organizations
     * @param list<string>                        $tags
     */
    public function create(PersonCard $contact, array $organizations, array $tags): ContactInListResource
    {
        return new ContactInListResource(
            id: $contact->id,
            name: new PersonNameResource($contact->firstName, $contact->lastName),
            title: $contact->civility,
            jobTitle: $contact->jobTitle,
            contactDetails: PersonContactDetailsResource::of($contact),
            masked: $contact->masked,
            organizations: $organizations,
            tags: $tags,
            contact: $this->links->to('api_contacts_show', ['id' => $contact->id]),
        );
    }
}
