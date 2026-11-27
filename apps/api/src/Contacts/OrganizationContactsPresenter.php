<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\OrganizationContactLinksResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use Prospecter\Core\Prospecting\Application\Contact\ListOrganizationContacts\ListOrganizationContactsOutput;
use Prospecter\Core\Prospecting\Application\Contact\ListOrganizationContacts\OrganizationContacts;

/**
 * Presents what Prospecting shows of an organization's contacts (ADR 0085, 0217): each contact links to their card,
 * and to their removal from the organization for a member with the right to manage personal data.
 */
final class OrganizationContactsPresenter implements ListOrganizationContactsOutput
{
    /** @var array<string, OrganizationContactLinksResource> by contact id, in the order attached */
    public private(set) array $links = [];

    public function __construct(
        private readonly Links $routes,
        private readonly IntentLinks $intentLinks,
    ) {
    }

    public function present(OrganizationContacts $contacts): void
    {
        $removable = $this->intentLinks->shows('RemoveContact', $contacts->organization->possibleIntents, User::PERSONAL_DATA);

        foreach ($contacts->contactIds as $contactId) {
            $this->links[$contactId] = new OrganizationContactLinksResource(
                $this->routes->to('api_contacts_show', ['id' => $contactId]),
                $removable ? $this->routes->to('api_organizations_remove_contact', ['id' => $contacts->organization->id, 'personId' => $contactId]) : null,
            );
        }
    }
}
