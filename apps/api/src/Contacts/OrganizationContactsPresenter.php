<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\OrganizationContactLinksResource;
use App\Contacts\Resource\OrganizationContactResource;
use App\Contacts\Resource\OrganizationContactsResource;
use App\PersonalData\Resource\PersonContactDetailsResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\ExternalLinkResource;
use Prospecter\Core\Prospecting\Application\Contact\ListOrganizationContacts\ListOrganizationContactsOutput;
use Prospecter\Core\Prospecting\Application\Contact\ListOrganizationContacts\OrganizationContacts;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\PersonDescription;

/**
 * Presents an organization's contacts as Prospecting read them, unmasked or masked (ADR 0085, 0217). Each contact links
 * to their card, and to their removal from the organization for a member with the right to manage personal data.
 */
final class OrganizationContactsPresenter implements ListOrganizationContactsOutput
{
    public private(set) OrganizationContactsResource $resource;

    public function __construct(
        private readonly Links $links,
        private readonly IntentLinks $intentLinks,
    ) {
    }

    public function present(OrganizationContacts $contacts): void
    {
        $this->resource = new OrganizationContactsResource(array_map(fn (PersonDescription $contact): OrganizationContactResource => $this->contactOf($contact, $contacts->organization), $contacts->contacts));
    }

    private function contactOf(PersonDescription $contact, OrganizationSheet $organization): OrganizationContactResource
    {
        return new OrganizationContactResource(
            id: $contact->personId,
            name: new PersonNameResource($contact->firstName, $contact->lastName),
            title: $contact->title,
            jobTitle: $contact->jobTitle,
            contactDetails: new PersonContactDetailsResource($contact->email, $contact->phoneNumber, $contact->language, ExternalLinkResource::allOf($contact->links)),
            masked: $contact->masked,
            links: new OrganizationContactLinksResource(
                $this->links->to('api_contacts_show', ['id' => $contact->personId]),
                $this->intentLinks->shows('RemoveContact', $organization->possibleIntents, User::PERSONAL_DATA) ? $this->links->to('api_organizations_remove_contact', ['id' => $organization->id, 'personId' => $contact->personId]) : null,
            ),
        );
    }
}
