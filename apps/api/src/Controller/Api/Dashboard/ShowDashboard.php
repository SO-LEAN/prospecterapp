<?php

namespace App\Controller\Api\Dashboard;

use App\Api\OrganizationReader;
use App\Api\Resource\DashboardResource;
use App\Api\Resource\OrganizationSummaryResource;
use App\Api\Resource\ProblemResource;
use App\Entity\User;
use App\Presenter\OrganizationPagePresenter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Application\Organization\SearchProspectedOrganizations\SearchProspectedOrganizations;
use Prospecter\Core\Application\Organization\SearchProspectedOrganizations\SearchProspectedOrganizationsHandler;
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
        private readonly OrganizationReader $organizations,
        private readonly SearchProspectedOrganizationsHandler $searchProspectedOrganizations,
        private readonly OrganizationPagePresenter $pagePresenter,
    ) {
    }

    #[Route('/api/dashboard', name: 'api_dashboard', methods: ['GET'])]
    #[OA\Get(summary: 'Statistics of my organization and the organizations to place on a map')]
    #[OA\Response(response: 200, description: 'My dashboard', content: new Model(type: DashboardResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $myOrganization = $this->organizations->read($user->getUserId(), $user->getOrganizationId());
        $page = $this->pagePresenter->present(($this->searchProspectedOrganizations)(new SearchProspectedOrganizations($user->getUserId(), 1, '', self::MAX_MARKERS)));

        return $this->json(new DashboardResource(
            organizationName: (string) $myOrganization->legalIdentity->corporateName,
            stats: ['activeOrganizations' => $myOrganization->activeOrganizations],
            markers: array_values(array_filter($page->items, static fn (OrganizationSummaryResource $item): bool => null !== $item->location)),
        ));
    }
}
