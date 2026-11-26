<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\ContactListLinksResource;
use App\Shared\Links;
use Prospecter\Core\PersonalData\Application\Contact\ContactPage;
use Prospecter\Core\PersonalData\Application\Contact\SearchContacts\SearchContactsOutput;

/**
 * Presents what PersonalData shows of a page of the account's contacts: the cards, unmasked or masked, and the page's
 * links, those its intents offer included. One presenter serves one call (ADR 0085).
 */
final class ContactPagePresenter implements SearchContactsOutput
{
    public private(set) ContactPage $page;

    public private(set) ContactListLinksResource $links;

    public function __construct(private readonly Links $routes, private readonly string $query)
    {
    }

    public function present(ContactPage $page): void
    {
        $this->page = $page;
        $this->links = new ContactListLinksResource(
            $this->routes->to('api_contacts_search', array_filter(['q' => $this->query])),
            in_array('SearchByText', $page->possibleIntents, true) ? $this->routes->to('api_contacts_search') : null,
            in_array('Collect', $page->possibleIntents, true) ? $this->routes->to('api_contacts_collect') : null,
            null === $page->next ? null : $this->routes->to('api_contacts_search', array_filter(['q' => $this->query, 'after' => $page->next])),
            $this->routes->to('api_contacts_search_by_tags'),
        );
    }
}
