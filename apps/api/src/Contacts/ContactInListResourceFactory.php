<?php

namespace App\Contacts;

use App\Contacts\Resource\ContactInListResource;
use App\Contacts\Resource\OrganizationOfContactResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Shared\Links;
use App\Shared\Resource\ContactDetailsResource;
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
     */
    public function create(PersonCard $contact, array $organizations): ContactInListResource
    {
        return new ContactInListResource(
            id: $contact->id,
            name: new PersonNameResource($contact->firstName, $contact->lastName),
            jobTitle: $contact->jobTitle,
            contactDetails: new ContactDetailsResource($contact->email, $contact->phoneNumber, $contact->language),
            masked: $contact->masked,
            organizations: $organizations,
            contact: $this->links->to('api_contacts_show', ['id' => $contact->id]),
        );
    }
}
