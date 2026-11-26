<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\ContactListResource;
use App\Shared\Links;
use Prospecter\Core\PersonalData\Application\Contact\SearchContacts\SearchContacts;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Composes the contacts of a member's account (ADR 0217). PersonalData reads a page of cards, unmasked or masked, and
 * links it. Prospecting gives each contact's organizations, and Campaigns the tags each one carries, joined by the
 * contacts' ids.
 */
final readonly class ContactSearchComposition
{
    public function __construct(
        private QueryHandler $searchContacts,
        private ContactsInList $contactsInList,
        private Links $links,
    ) {
    }

    public function search(string $memberId, string $query, ?string $after): ContactListResource
    {
        $presenter = new ContactPagePresenter($this->links, $query);
        $this->searchContacts->__invoke(new SearchContacts($memberId, $query, $after), $presenter);

        return new ContactListResource(array_values($this->contactsInList->ofCards($memberId, $presenter->page->contacts)), $presenter->links);
    }
}
