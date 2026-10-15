<?php

namespace App\Controller\Api\Dashboard;

use App\Api\Resource\DashboardResource;
use App\Api\Resource\OrganizationPageResource;
use App\Api\Resource\OrganizationResource;
use App\Api\Resource\OrganizationSummaryResource;
use App\Api\Resource\ProblemResource;
use App\Entity\User;
use App\Presenter\OrganizationPagePresenter;
use App\Presenter\OrganizationResourcePresenter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Application\Organization\ConsultOrganization\ConsultOrganizationRequest;
use Prospecter\Core\Application\Organization\SearchProspectedOrganizations\SearchProspectedOrganizationsRequest;
use Prospecter\Core\Application\UseCasesFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Dashboard')]
#[IsGranted('ROLE_PROSPECTOR')]
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
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        /** @var OrganizationResource $myOrganization */
        $myOrganization = $this->useCases->consultOrganization(new ConsultOrganizationRequest($user->getUserId(), $user->getOrganizationId()), $this->organizationPresenter);
        /** @var OrganizationPageResource $page */
        $page = $this->useCases->searchProspectedOrganizations(new SearchProspectedOrganizationsRequest($user->getUserId(), 1, '', self::MAX_MARKERS), $this->pagePresenter);

        return $this->json(new DashboardResource(
            organizationName: (string) $myOrganization->legalIdentity->corporateName,
            stats: ['activeOrganizations' => $myOrganization->activeOrganizations],
            markers: array_values(array_filter($page->items, static fn (OrganizationSummaryResource $item): bool => null !== $item->location)),
        ));
    }
}
