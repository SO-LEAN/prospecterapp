<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\ContactInListResource;
use App\Contacts\Resource\TaggedContactListResource;
use App\Shared\Links;
use Prospecter\Core\Campaigns\Application\Tagging\SearchContactsByTags\SearchContactsByTags;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Composes the contacts that carry every chosen tag (ADR 0217). Campaigns finds them, counts them and links the page.
 * PersonalData, Prospecting and Campaigns then give each contact in a list, joined by their id, in the order Campaigns
 * found them. A contact forgotten between the two reads of the request has no card, so no line.
 */
final readonly class TaggedContactsComposition
{
    public function __construct(
        private QueryHandler $searchContactsByTags,
        private ContactsInList $contactsInList,
        private Links $links,
    ) {
    }

    /**
     * @param list<string> $chosen the tags' names, without their mark
     * @param string       $tags   the chosen tags, as the query names them
     */
    public function search(string $memberId, array $chosen, string $tags, ?string $after): TaggedContactListResource
    {
        $presenter = new TaggedContactPagePresenter($this->links, $tags);
        $this->searchContactsByTags->__invoke(new SearchContactsByTags($memberId, $chosen, $after), $presenter);
        $page = $presenter->page;
        $contacts = $this->contactsInList->of($memberId, $page->contactIds);

        return new TaggedContactListResource(
            array_values(array_filter(array_map(static fn (string $id): ?ContactInListResource => $contacts[$id] ?? null, $page->contactIds))),
            $page->found,
            $presenter->links,
        );
    }
}
