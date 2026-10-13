<?php

namespace App\Controller\Api\Dashboard;

use App\Api\Resource\DashboardResource;
use App\Api\Resource\OrganizationPageResource;
use App\Api\Resource\OrganizationSummaryResource;
use App\Entity\User;
use App\Presenter\GetOrganizationPresenterImpl;
use App\Presenter\OrganizationPagePresenter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsRequest;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationRequest;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationResponse;
use Prospecter\Core\UseCase\UseCasesFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag('Dashboard')]
final class ShowDashboard extends AbstractController
{
    private const int MAX_MARKERS = 1000;

    public function __construct(
        private readonly UseCasesFacade $useCases,
        private readonly OrganizationPagePresenter $pagePresenter,
    ) {
    }

    #[Route('/api/dashboard', name: 'api_dashboard', methods: ['GET'])]
    #[OA\Get(summary: 'Statistics of my organization and the organizations to place on a map')]
    #[OA\Response(response: 200, description: 'My dashboard', content: new Model(type: DashboardResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        /** @var GetOrganizationResponse $organization */
        $organization = $this->useCases->getOrganization(new GetOrganizationRequest($user->getOrganizationId()), new GetOrganizationPresenterImpl(), $user);
        /** @var OrganizationPageResource $page */
        $page = $this->useCases->findMyOwnOrganizations(new FindMyOwnOrganizationsRequest(1, '', self::MAX_MARKERS), $this->pagePresenter, $user);

        return $this->json(new DashboardResource(
            organizationName: (string) $organization->getCorporateName(),
            stats: array_map(intval(...), $organization->getStats()),
            markers: array_values(array_filter($page->items, static fn (OrganizationSummaryResource $item): bool => null !== $item->location)),
        ));
    }
}
