<?php

declare(strict_types=1);

namespace App\Prospecting\Controller;

use App\Prospecting\Input\OrganizationListQuery;
use App\Prospecting\OrganizationSearchReader;
use App\Prospecting\Resource\OrganizationPageResource;
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

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class SearchOrganizationsController
{
    public function __construct(private OrganizationSearchReader $organizations, private JsonResponses $jsonResponses)
    {
    }

    #[Route('/api/organizations', name: 'api_organizations_search', methods: ['GET'])]
    #[OA\Get(summary: 'Search the organizations that my account prospects, by corporate name or email')]
    #[OA\Response(response: 200, description: 'A slice of organizations, most recent first. The link next leads to the next slice', content: new Model(type: OrganizationPageResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user, #[MapQueryString] OrganizationListQuery $query = new OrganizationListQuery()): JsonResponse
    {
        return $this->jsonResponses->of($this->organizations->search($user->memberId(), $query->q, $query->after));
    }
}
