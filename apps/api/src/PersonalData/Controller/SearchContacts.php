<?php

namespace App\PersonalData\Controller;

use App\PersonalData\Input\ContactListQuery;
use App\PersonalData\Presenter\ContactResourcePresenter;
use App\PersonalData\Resource\ContactPageResource;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\SearchContacts\SearchContacts as SearchContactsQuery;
use Prospecter\Core\PersonalData\Application\SearchContacts\SearchContactsHandler;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final class SearchContacts extends AbstractController
{
    private readonly RefusalTranslatingHandler $searchContacts;

    public function __construct(SearchContactsHandler $searchContacts, private readonly ContactResourcePresenter $presenter)
    {
        $this->searchContacts = new RefusalTranslatingHandler($searchContacts);
    }

    #[Route('/api/contacts', name: 'api_contacts_search', methods: ['GET'])]
    #[OA\Get(summary: 'Search the persons my organization prospects, by name or email; a search by text needs the right to manage personal data')]
    #[OA\Response(response: 200, description: 'A page of contacts, most recent first', content: new Model(type: ContactPageResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user, #[MapQueryString] ContactListQuery $query = new ContactListQuery()): JsonResponse
    {
        return $this->json($this->presenter->presentPage(($this->searchContacts)(new SearchContactsQuery($user->memberId(), $query->page, $query->q))));
    }
}
