<?php

namespace App\Dashboard;

use App\Me\MyAccountReader;
use App\Prospecting\Presenter\OrganizationPagePresenter;
use App\Prospecting\Resource\OrganizationSummaryResource;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\SearchProspectedOrganizations;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
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
        private readonly MyAccountReader $account,
        private readonly RefusalTranslatingHandler $searchProspectedOrganizations,
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
        $myAccount = $this->account->read($user->memberId());
        $page = $this->pagePresenter->present(($this->searchProspectedOrganizations)(new SearchProspectedOrganizations($user->memberId(), max: self::MAX_MARKERS)));

        return $this->json(new DashboardResource(
            organizationName: (string) $myAccount->organization->legalIdentity->corporateName,
            stats: ['activeOrganizations' => count($page->items)],
            markers: array_values(array_filter($page->items, static fn (OrganizationSummaryResource $item): bool => null !== $item->location)),
        ));
    }
}
