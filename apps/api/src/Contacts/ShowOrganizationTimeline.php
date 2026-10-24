<?php

namespace App\Contacts;

use App\Contacts\Resource\OrganizationTimelineResource;
use App\Prospecting\Input\TimelineQuery;
use App\Prospecting\OrganizationVoter;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\OrganizationResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ShowOrganizationTimeline extends AbstractController
{
    public function __construct(
        private readonly OrganizationTimelineReader $timelines,
    ) {
    }

    #[Route('/api/organizations/{id}/exchanges', name: 'api_organizations_timeline', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[IsGranted(OrganizationVoter::CONSULT_TIMELINE, subject: 'organization')]
    #[OA\Get(summary: 'The exchanges with the organization and with its contacts, the most recent first, each with who communicated')]
    #[OA\Response(response: 200, description: 'A slice of the exchanges, the next one at the link next', content: new Model(type: OrganizationTimelineResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationResponse $organization, #[CurrentUser] User $user, #[MapQueryString] TimelineQuery $query = new TimelineQuery()): JsonResponse
    {
        return $this->json($this->timelines->read($user->memberId(), $organization, $query->after));
    }
}
