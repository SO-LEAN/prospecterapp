<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\ContactInListResource;
use App\Contacts\Resource\ContactListLinksResource;
use App\Contacts\Resource\ContactListResource;
use App\Shared\Links;
use Prospecter\Core\PersonalData\Application\Contact\ContactPage;
use Prospecter\Core\PersonalData\Application\Contact\SearchContacts\KeptContactPage;
use Prospecter\Core\PersonalData\Application\Contact\SearchContacts\SearchContacts;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Reads the contacts of a member's account from three contexts. PersonalData reads a page of persons, unmasked or
 * masked. Prospecting gives each contact's organizations, and Campaigns gives the tags each contact carries. Each
 * context answers in one batch.
 */
final readonly class ContactSearchReader
{
    public function __construct(
        private QueryHandler $searchContacts,
        private OrganizationsOfContacts $organizationsOfContacts,
        private TagsOfContacts $tagsOfContacts,
        private ContactInListResourceFactory $contactInListResourceFactory,
        private Links $links,
    ) {
    }

    public function search(string $memberId, string $query, ?string $after): ContactListResource
    {
        $kept = new KeptContactPage();
        $this->searchContacts->__invoke(new SearchContacts($memberId, $query, $after), $kept);
        $page = $kept->page;
        $contactIds = array_map(static fn (PersonCard $contact): string => $contact->id, $page->contacts);
        $organizations = $this->organizationsOfContacts->read($memberId, $contactIds);
        $tags = $this->tagsOfContacts->read($memberId, $contactIds);

        return new ContactListResource(
            array_map(fn (PersonCard $contact): ContactInListResource => $this->contactInListResourceFactory->create($contact, $organizations[$contact->id] ?? [], $tags[$contact->id] ?? []), $page->contacts),
            $this->linksOf($page, $query),
        );
    }

    private function linksOf(ContactPage $page, string $query): ContactListLinksResource
    {
        return new ContactListLinksResource(
            $this->links->to('api_contacts_search', array_filter(['q' => $query])),
            in_array('SearchByText', $page->possibleIntents, true) ? $this->links->to('api_contacts_search') : null,
            null === $page->next ? null : $this->links->to('api_contacts_search', array_filter(['q' => $query, 'after' => $page->next])),
            $this->links->to('api_contacts_search_by_tags'),
        );
    }
}
