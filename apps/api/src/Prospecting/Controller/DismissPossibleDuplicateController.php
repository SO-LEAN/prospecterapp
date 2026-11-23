<?php

declare(strict_types=1);

namespace App\Prospecting\Controller;

use App\Prospecting\OrganizationReader;
use App\Prospecting\OrganizationVoter;
use App\Prospecting\Resource\OrganizationResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\DismissPossibleDuplicate\DismissPossibleDuplicate;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class DismissPossibleDuplicateController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private OrganizationReader $organizationReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/organizations/{id}/possible-duplicates/{duplicateId}', name: 'api_organizations_dismiss_possible_duplicate', requirements: ['id' => Requirement::UUID, 'duplicateId' => Requirement::UUID], methods: ['DELETE'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[OA\Delete(summary: 'State, after checking, that the organization is not a duplicate of the one it resembles. Neither one is flagged for the other again')]
    #[OA\Response(response: 200, description: 'The organization without this possible duplicate', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one in another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'The organization is not suspected of being a duplicate of this one', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization, string $duplicateId, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new DismissPossibleDuplicate(prospectorId: $user->memberId(), id: $organization->id, duplicateId: $duplicateId, executedAt: $this->clock->now()));

        return $this->jsonResponses->of($this->organizationReader->read($organization->id));
    }
}
