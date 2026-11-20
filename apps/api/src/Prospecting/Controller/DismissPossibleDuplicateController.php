<?php

namespace App\Prospecting\Controller;

use App\Prospecting\OrganizationReader;
use App\Prospecting\OrganizationVoter;
use App\Prospecting\Resource\OrganizationResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\DismissPossibleDuplicate\DismissPossibleDuplicate;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final class DismissPossibleDuplicateController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly OrganizationReader $organizationReader,
    ) {
    }

    #[Route('/api/organizations/{id}/possible-duplicates/{duplicateId}', name: 'api_organizations_dismiss_possible_duplicate', requirements: ['id' => Requirement::UUID, 'duplicateId' => Requirement::UUID], methods: ['DELETE'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[OA\Delete(summary: 'Say, once checked, that the organization is no duplicate of the one it resembles; neither is flagged for the other any more')]
    #[OA\Response(response: 200, description: 'The organization without this possible duplicate', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'The organization is not suspected of being a duplicate of this one', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization, string $duplicateId, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new DismissPossibleDuplicate($user->memberId(), $organization->id, $duplicateId, $this->clock->now()));

        return $this->json($this->organizationReader->read($organization->id));
    }
}
