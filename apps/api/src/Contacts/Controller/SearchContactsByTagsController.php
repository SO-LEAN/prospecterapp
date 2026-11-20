<?php

namespace App\Contacts\Controller;

use App\Contacts\Input\TaggedContactsQuery;
use App\Contacts\Resource\TaggedContactListResource;
use App\Contacts\TaggedContactsReader;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The contacts carrying every tag chosen, composed by `TaggedContactsReader`. Every prospector searches by tags, tags
 * being no personal data.
 */
#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final class SearchContactsByTagsController extends AbstractController
{
    public function __construct(private readonly TaggedContactsReader $contacts)
    {
    }

    #[Route('/api/tagged-contacts', name: 'api_contacts_search_by_tags', methods: ['GET'])]
    #[OA\Get(summary: 'Search the contacts of my organization carrying every tag chosen, their own, inherited or automatic, counted')]
    #[OA\Response(response: 200, description: 'A slice of contacts, most recent first, the next one at the link next', content: new Model(type: TaggedContactListResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'No tag, more than five, or one that makes no tag', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user, #[MapQueryString] TaggedContactsQuery $query = new TaggedContactsQuery()): JsonResponse
    {
        return $this->json($this->contacts->search($user->memberId(), $query->chosen(), $query->tags, $query->after));
    }
}
