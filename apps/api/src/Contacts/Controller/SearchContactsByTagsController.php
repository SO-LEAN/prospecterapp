<?php

declare(strict_types=1);

namespace App\Contacts\Controller;

use App\Contacts\Input\TaggedContactsQuery;
use App\Contacts\Resource\TaggedContactListResource;
use App\Contacts\TaggedContactsReader;
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
 * Searches the contacts that carry every chosen tag. `TaggedContactsReader` reads the answer. Every prospector may
 * search by tags, because tags are not personal data.
 */
#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class SearchContactsByTagsController
{
    public function __construct(private TaggedContactsReader $contacts, private JsonResponses $jsonResponses)
    {
    }

    #[Route('/api/tagged-contacts', name: 'api_contacts_search_by_tags', methods: ['GET'])]
    #[OA\Get(summary: 'Search my organization\'s contacts that carry every chosen tag, and count them. A tag may be their own, inherited or automatic')]
    #[OA\Response(response: 200, description: 'A slice of contacts, most recent first, the next one at the link next', content: new Model(type: TaggedContactListResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'No tag, more than five, or one that makes no tag', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user, #[MapQueryString] TaggedContactsQuery $query = new TaggedContactsQuery()): JsonResponse
    {
        return $this->jsonResponses->of($this->contacts->search($user->memberId(), $query->chosen(), $query->tags, $query->after));
    }
}
