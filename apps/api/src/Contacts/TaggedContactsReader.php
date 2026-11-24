<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\ContactInListResource;
use App\Contacts\Resource\TaggedContactListLinksResource;
use App\Contacts\Resource\TaggedContactListResource;
use App\Shared\Links;
use Prospecter\Core\Campaigns\Application\Tagging\SearchContactsByTags\KeptTaggedContactPage;
use Prospecter\Core\Campaigns\Application\Tagging\SearchContactsByTags\SearchContactsByTags;
use Prospecter\Core\Campaigns\Application\Tagging\SearchContactsByTags\TaggedContactPage;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\ConsultPersons\KeptPersons;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Reads the contacts that carry every chosen tag, from three contexts. Campaigns finds them and counts them.
 * PersonalData reads them, unmasked or masked. Prospecting gives each contact's organizations, and Campaigns gives the
 * tags each contact carries. Each context answers in one batch.
 */
final readonly class TaggedContactsReader
{
    public function __construct(
        private QueryHandler $searchContactsByTags,
        private QueryHandler $consultPersons,
        private OrganizationsOfContacts $organizationsOfContacts,
        private TagsOfContacts $tagsOfContacts,
        private ContactInListResourceFactory $contactInListResourceFactory,
        private Links $links,
    ) {
    }

    /**
     * @param list<string> $chosen the tags' names, without their mark
     * @param string       $tags   the chosen tags, as the query names them
     */
    public function search(string $memberId, array $chosen, string $tags, ?string $after): TaggedContactListResource
    {
        $kept = new KeptTaggedContactPage();
        $this->searchContactsByTags->__invoke(new SearchContactsByTags($memberId, $chosen, $after), $kept);
        $page = $kept->page ?? throw new \LogicException('The search presented no page');
        $contacts = $this->read($memberId, $page->contactIds);
        $organizations = $this->organizationsOfContacts->read($memberId, $page->contactIds);
        $tagsOfContacts = $this->tagsOfContacts->read($memberId, $page->contactIds);

        return new TaggedContactListResource(
            array_map(fn (PersonCard $contact): ContactInListResource => $this->contactInListResourceFactory->create($contact, $organizations[$contact->id] ?? [], $tagsOfContacts[$contact->id] ?? []), $contacts),
            $page->found,
            $this->linksOf($page, $tags),
        );
    }

    /**
     * @param list<string> $contactIds
     *
     * @return list<PersonCard> in their order, without those PersonalData no longer reads
     */
    private function read(string $reader, array $contactIds): array
    {
        $kept = new KeptPersons();
        $this->consultPersons->__invoke(new ConsultPersons($reader, $contactIds), $kept);

        return array_values(array_filter(array_map(static fn (string $id): ?PersonCard => $kept->persons[$id] ?? null, $contactIds)));
    }

    private function linksOf(TaggedContactPage $page, string $tags): TaggedContactListLinksResource
    {
        return new TaggedContactListLinksResource(
            $this->links->to('api_contacts_search_by_tags', ['tags' => $tags]),
            null === $page->next ? null : $this->links->to('api_contacts_search_by_tags', ['tags' => $tags, 'after' => $page->next]),
        );
    }
}
