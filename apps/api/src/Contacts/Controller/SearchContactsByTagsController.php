<?php

namespace App\Contacts\Controller;

use App\Contacts\ContactInListResourceFactory;
use App\Contacts\Input\TaggedContactsQuery;
use App\Contacts\OrganizationsOfContacts;
use App\Contacts\Resource\ContactInListResource;
use App\Contacts\Resource\TaggedContactListLinksResource;
use App\Contacts\Resource\TaggedContactListResource;
use App\Contacts\TagsOfContacts;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Campaigns\Application\SearchContactsByTags\KeptTaggedContactPage;
use Prospecter\Core\Campaigns\Application\SearchContactsByTags\SearchContactsByTags;
use Prospecter\Core\Campaigns\Application\SearchContactsByTags\TaggedContactPage;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\ConsultPersons\KeptPersons;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The contacts carrying every tag chosen, composed of three contexts: Campaigns finds them and counts them,
 * PersonalData reads them, in clear or masked, Prospecting tells the organizations of each and Campaigns the tags
 * each carries, in one batch each. Every prospector searches by tags, tags being no personal data.
 */
#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final class SearchContactsByTagsController extends AbstractController
{
    public function __construct(
        private readonly QueryHandler $searchContactsByTags,
        private readonly QueryHandler $consultPersons,
        private readonly OrganizationsOfContacts $organizationsOfContacts,
        private readonly TagsOfContacts $tagsOfContacts,
        private readonly ContactInListResourceFactory $contactInListResourceFactory,
        private readonly Links $links,
    ) {
    }

    #[Route('/api/tagged-contacts', name: 'api_contacts_search_by_tags', methods: ['GET'])]
    #[OA\Get(summary: 'Search the contacts of my organization carrying every tag chosen, their own, inherited or automatic, counted')]
    #[OA\Response(response: 200, description: 'A slice of contacts, most recent first, the next one at the link next', content: new Model(type: TaggedContactListResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'No tag, more than five, or one that makes no tag', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user, #[MapQueryString] TaggedContactsQuery $query = new TaggedContactsQuery()): JsonResponse
    {
        $kept = new KeptTaggedContactPage();
        ($this->searchContactsByTags)(new SearchContactsByTags($user->memberId(), $query->chosen(), $query->after), $kept);
        $page = $kept->page ?? throw new \LogicException('The search presented no page');
        $contacts = $this->read($user->memberId(), $page->contactIds);
        $organizations = $this->organizationsOfContacts->read($user->memberId(), $page->contactIds);
        $tags = $this->tagsOfContacts->read($user->memberId(), $page->contactIds);

        return $this->json(new TaggedContactListResource(
            array_map(fn (PersonCard $contact): ContactInListResource => $this->contactInListResourceFactory->create($contact, $organizations[$contact->id] ?? [], $tags[$contact->id] ?? []), $contacts),
            $page->found,
            $this->linksOf($page, $query->tags),
        ));
    }

    /**
     * @param list<string> $contactIds
     *
     * @return list<PersonCard> in their order, those PersonalData no longer reads left out
     */
    private function read(string $reader, array $contactIds): array
    {
        $kept = new KeptPersons();
        ($this->consultPersons)(new ConsultPersons($reader, $contactIds), $kept);

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
