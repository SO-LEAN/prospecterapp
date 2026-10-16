<?php

namespace App\Prospecting\Controller;

use App\Prospecting\Input\OrganizationListQuery;
use App\Prospecting\Presenter\OrganizationPagePresenter;
use App\Prospecting\Resource\OrganizationPageResource;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\SearchProspectedOrganizations;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final class SearchOrganizations extends AbstractController
{
    public function __construct(
        private readonly RefusalTranslatingHandler $searchProspectedOrganizations,
        private readonly OrganizationPagePresenter $presenter,
    ) {
    }

    #[Route('/api/organizations', name: 'api_organizations_search', methods: ['GET'])]
    #[OA\Get(summary: 'Search the organizations my organization prospects, by corporate name or email')]
    #[OA\Response(response: 200, description: 'A slice of organizations, most recent first, the next one at the link next', content: new Model(type: OrganizationPageResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user, #[MapQueryString] OrganizationListQuery $query = new OrganizationListQuery()): JsonResponse
    {
        return $this->json($this->presenter->present(($this->searchProspectedOrganizations)(new SearchProspectedOrganizations($user->memberId(), $query->q, $query->after)), $query->q));
    }
}
