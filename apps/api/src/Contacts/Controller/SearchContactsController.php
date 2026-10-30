<?php

namespace App\Contacts\Controller;

use App\Contacts\ContactInListResourceFactory;
use App\Contacts\OrganizationsOfContacts;
use App\Contacts\Resource\ContactInListResource;
use App\Contacts\Resource\ContactListLinksResource;
use App\Contacts\Resource\ContactListResource;
use App\PersonalData\Input\ContactListQuery;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\SearchContacts\ContactPage;
use Prospecter\Core\PersonalData\Application\SearchContacts\KeptContactPage;
use Prospecter\Core\PersonalData\Application\SearchContacts\SearchContacts;
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
final class SearchContactsController extends AbstractController
{
    public function __construct(
        private readonly RefusalTranslatingHandler $searchContacts,
        private readonly OrganizationsOfContacts $organizationsOfContacts,
        private readonly ContactInListResourceFactory $contactInListResourceFactory,
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
        $kept = new KeptContactPage();
        ($this->searchContacts)(new SearchContacts($user->memberId(), $query->q, $query->after), $kept);
        $page = $kept->page;
        $organizations = $this->organizationsOfContacts->read($user->memberId(), array_map(static fn (PersonCard $contact): string => $contact->id, $page->contacts));

        return $this->json(new ContactListResource(
            array_map(fn (PersonCard $contact): ContactInListResource => $this->contactInListResourceFactory->create($contact, $organizations[$contact->id] ?? []), $page->contacts),
            $this->linksOf($page, $query->q),
        ));
    }

    private function linksOf(ContactPage $page, string $query): ContactListLinksResource
    {
        return new ContactListLinksResource(
            $this->links->to('api_contacts_search', array_filter(['q' => $query])),
            in_array('SearchByText', $page->possibleIntents, true) ? $this->links->to('api_contacts_search') : null,
            null === $page->next ? null : $this->links->to('api_contacts_search', array_filter(['q' => $query, 'after' => $page->next])),
        );
    }
}
