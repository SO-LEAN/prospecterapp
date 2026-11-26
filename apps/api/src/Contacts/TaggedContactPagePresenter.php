<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\TaggedContactListLinksResource;
use App\Shared\Links;
use Prospecter\Core\Campaigns\Application\Tagging\SearchContactsByTags\SearchContactsByTagsOutput;
use Prospecter\Core\Campaigns\Application\Tagging\SearchContactsByTags\TaggedContactPage;

/**
 * Presents what Campaigns shows of the contacts that carry every chosen tag: the page it found and counted, and its
 * links. One presenter serves one call (ADR 0085).
 */
final class TaggedContactPagePresenter implements SearchContactsByTagsOutput
{
    public private(set) TaggedContactPage $page;

    public private(set) TaggedContactListLinksResource $links;

    /**
     * @param string $tags the chosen tags, as the query names them
     */
    public function __construct(private readonly Links $routes, private readonly string $tags)
    {
    }

    public function present(TaggedContactPage $page): void
    {
        $this->page = $page;
        $this->links = new TaggedContactListLinksResource(
            $this->routes->to('api_contacts_search_by_tags', ['tags' => $this->tags]),
            null === $page->next ? null : $this->routes->to('api_contacts_search_by_tags', ['tags' => $this->tags, 'after' => $page->next]),
        );
    }
}
