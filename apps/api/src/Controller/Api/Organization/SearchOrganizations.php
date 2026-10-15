<?php

namespace App\Controller\Api\Organization;

use App\Api\Input\OrganizationListQuery;
use App\Api\Resource\OrganizationPageResource;
use App\Entity\User;
use App\Presenter\OrganizationPagePresenter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Application\Organization\SearchProspectedOrganizationsRequest;
use Prospecter\Core\Application\UseCasesFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag('Organizations')]
final class SearchOrganizations extends AbstractController
{
    public function __construct(
        private readonly UseCasesFacade $useCases,
        private readonly OrganizationPagePresenter $presenter,
    ) {
    }

    #[Route('/api/organizations', name: 'api_organizations_search', methods: ['GET'])]
    #[OA\Get(summary: 'Search the organizations my organization prospects, by corporate name or email')]
    #[OA\Response(response: 200, description: 'A page of organizations, most recent first', content: new Model(type: OrganizationPageResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    public function __invoke(#[CurrentUser] User $user, #[MapQueryString] OrganizationListQuery $query = new OrganizationListQuery()): JsonResponse
    {
        return $this->json($this->useCases->searchProspectedOrganizations(new SearchProspectedOrganizationsRequest($query->page, $query->q), $this->presenter, $user));
    }
}
