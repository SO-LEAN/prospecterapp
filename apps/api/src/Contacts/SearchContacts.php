<?php

namespace App\Contacts;

use App\Contacts\Resource\ContactInListResource;
use App\Contacts\Resource\ContactListLinksResource;
use App\Contacts\Resource\ContactListResource;
use App\PersonalData\Input\ContactListQuery;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\PersonalData\Application\SearchContacts\SearchContacts as SearchContactsQuery;
use Prospecter\Core\PersonalData\Application\SearchContacts\SearchContactsResponse;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The contacts of my organization, composed of two contexts: PersonalData reads a page of persons, in clear or
 * masked; Prospecting tells the organizations of each, in one batch.
 */
#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final class SearchContacts extends AbstractController
{
    public function __construct(
        private readonly RefusalTranslatingHandler $searchContacts,
        private readonly OrganizationsOfContacts $organizations,
        private readonly ContactInListPresenter $contacts,
        private readonly Links $links,
    ) {
    }

    #[Route('/api/contacts', name: 'api_contacts_search', methods: ['GET'])]
    #[OA\Get(summary: 'Search the persons my organization prospects, by name or email, with their organizations; a search by text needs the right to manage personal data')]
    #[OA\Response(response: 200, description: 'A slice of contacts, most recent first, the next one at the link next', content: new Model(type: ContactListResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or a search by text without the right to manage personal data', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user, #[MapQueryString] ContactListQuery $query = new ContactListQuery()): JsonResponse
    {
        $page = ($this->searchContacts)(new SearchContactsQuery($user->memberId(), $query->q, $query->after));
        $organizations = $this->organizations->read($user->memberId(), array_map(static fn (PersonResponse $contact): string => $contact->id, $page->contacts));

        return $this->json(new ContactListResource(
            array_map(fn (PersonResponse $contact): ContactInListResource => $this->contacts->present($contact, $organizations[$contact->id] ?? []), $page->contacts),
            $this->linksOf($page, $query->q),
        ));
    }

    private function linksOf(SearchContactsResponse $page, string $query): ContactListLinksResource
    {
        return new ContactListLinksResource(
            $this->links->to('api_contacts_search', array_filter(['q' => $query])),
            in_array('SearchByText', $page->possibleIntents, true) ? $this->links->to('api_contacts_search') : null,
            null === $page->next ? null : $this->links->to('api_contacts_search', array_filter(['q' => $query, 'after' => $page->next])),
        );
    }
}
