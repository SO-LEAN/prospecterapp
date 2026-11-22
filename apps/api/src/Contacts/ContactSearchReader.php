<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\ContactInListResource;
use App\Contacts\Resource\ContactListLinksResource;
use App\Contacts\Resource\ContactListResource;
use App\Shared\Links;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\SearchContacts\ContactPage;
use Prospecter\Core\PersonalData\Application\SearchContacts\KeptContactPage;
use Prospecter\Core\PersonalData\Application\SearchContacts\SearchContacts;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * The contacts of the account of a member, composed of three contexts: PersonalData reads a page of persons, in
 * clear or masked; Prospecting tells the organizations of each, and Campaigns the tags each carries, in one batch
 * each.
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
        ($this->searchContacts)(new SearchContacts($memberId, $query, $after), $kept);
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
