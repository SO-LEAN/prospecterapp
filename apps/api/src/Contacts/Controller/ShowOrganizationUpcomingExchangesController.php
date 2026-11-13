<?php

namespace App\Contacts\Controller;

use App\Contacts\OrganizationTimelineReader;
use App\Contacts\Resource\OrganizationTimelineResource;
use App\Prospecting\Input\TimelineQuery;
use App\Prospecting\OrganizationVoter;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ShowOrganizationUpcomingExchangesController extends AbstractController
{
    public function __construct(
        private readonly OrganizationTimelineReader $organizationTimelineReader,
    ) {
    }

    #[Route('/api/organizations/{id}/upcoming-exchanges', name: 'api_organizations_upcoming_exchanges', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[OA\Get(summary: 'The exchanges to come with the organization and with its contacts, the soonest first, each with who it is with')]
    #[OA\Response(response: 200, description: 'A slice of the exchanges to come, the next one at the link next', content: new Model(type: OrganizationTimelineResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization, #[CurrentUser] User $user, #[MapQueryString] TimelineQuery $query = new TimelineQuery()): JsonResponse
    {
        return $this->json($this->organizationTimelineReader->readUpcoming($user->memberId(), $organization, $query->after));
    }
}
