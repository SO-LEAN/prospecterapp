<?php

declare(strict_types=1);

namespace App\Prospecting\Controller;

use App\Prospecting\Input\HoldingInput;
use App\Prospecting\OrganizationReader;
use App\Prospecting\OrganizationVoter;
use App\Prospecting\Resource\OrganizationResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\AttachToHolding\AttachToHolding;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class AttachToHoldingController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private OrganizationReader $organizationReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/organizations/{id}/holding', name: 'api_organizations_attach_to_holding', requirements: ['id' => Requirement::UUID], methods: ['PATCH'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[OA\Patch(summary: 'Attach the organization to a holding that my account prospects')]
    #[OA\Response(response: 200, description: 'The organization, attached to its holding', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one that my account neither owns nor prospects', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid holding id', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization, #[MapRequestPayload] HoldingInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new AttachToHolding(prospectorId: $user->memberId(), id: $organization->id, holdingId: $input->holdingId, executedAt: $this->clock->now()));

        return $this->jsonResponses->of($this->organizationReader->read($organization->id));
    }
}
