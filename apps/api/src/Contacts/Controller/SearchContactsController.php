<?php

declare(strict_types=1);

namespace App\Contacts\Controller;

use App\Contacts\ContactSearchComposition;
use App\Contacts\Resource\ContactListResource;
use App\PersonalData\Input\ContactListQuery;
use App\Security\User;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Searches my organization's contacts. `ContactSearchComposition` reads the answer.
 */
#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class SearchContactsController
{
    public function __construct(private ContactSearchComposition $contacts, private JsonResponses $jsonResponses)
    {
    }

    #[Route('/api/contacts', name: 'api_contacts_search', methods: ['GET'])]
    #[OA\Get(summary: 'Search the persons my organization prospects, with their organizations. Search by name, or by email with the right to manage personal data')]
    #[OA\Response(response: 200, description: 'A slice of contacts, most recent first, the next one at the link next', content: new Model(type: ContactListResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user, #[MapQueryString] ContactListQuery $query = new ContactListQuery()): JsonResponse
    {
        return $this->jsonResponses->of($this->contacts->search($user->memberId(), $query->q, $query->after));
    }
}
