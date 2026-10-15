<?php

namespace App\Controller\Api\Dashboard;

use App\Api\Resource\DashboardResource;
use App\Api\Resource\OrganizationPageResource;
use App\Api\Resource\OrganizationResource;
use App\Api\Resource\OrganizationSummaryResource;
use App\Entity\User;
use App\Presenter\OrganizationPagePresenter;
use App\Presenter\OrganizationResourcePresenter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Application\Organization\ConsultOrganizationRequest;
use Prospecter\Core\Application\Organization\SearchProspectedOrganizationsRequest;
use Prospecter\Core\Application\UseCasesFacade;
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
        private readonly OrganizationResourcePresenter $organizationPresenter,
    ) {
    }

    #[Route('/api/dashboard', name: 'api_dashboard', methods: ['GET'])]
    #[OA\Get(summary: 'Statistics of my organization and the organizations to place on a map')]
    #[OA\Response(response: 200, description: 'My dashboard', content: new Model(type: DashboardResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        /** @var OrganizationResource $myOrganization */
        $myOrganization = $this->useCases->consultOrganization(new ConsultOrganizationRequest($user->getOrganizationId()), $this->organizationPresenter, $user);
        /** @var OrganizationPageResource $page */
        $page = $this->useCases->searchProspectedOrganizations(new SearchProspectedOrganizationsRequest(1, '', self::MAX_MARKERS), $this->pagePresenter, $user);

        return $this->json(new DashboardResource(
            organizationName: (string) $myOrganization->legalIdentity->corporateName,
            stats: ['activeOrganizations' => $myOrganization->activeOrganizations],
            markers: array_values(array_filter($page->items, static fn (OrganizationSummaryResource $item): bool => null !== $item->location)),
        ));
    }
}
