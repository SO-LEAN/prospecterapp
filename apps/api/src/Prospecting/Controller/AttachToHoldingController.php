<?php

namespace App\Prospecting\Controller;

use App\Prospecting\Input\HoldingInput;
use App\Prospecting\OrganizationReader;
use App\Prospecting\OrganizationVoter;
use App\Prospecting\Resource\OrganizationResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\AttachToHolding\AttachToHolding;
use Prospecter\Core\Prospecting\Application\OrganizationResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final class AttachToHoldingController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly OrganizationReader $organizationReader,
    ) {
    }

    #[Route('/api/organizations/{id}/holding', name: 'api_organizations_attach_to_holding', requirements: ['id' => Requirement::UUID], methods: ['PATCH'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[OA\Patch(summary: 'Attach the organization to a holding my organization prospects')]
    #[OA\Response(response: 200, description: 'The organization in its holding', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one that is neither mine nor prospected by mine', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid holding id', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationResponse $organization, #[MapRequestPayload] HoldingInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new AttachToHolding($user->memberId(), $organization->id, $input->holdingId, $this->clock->now()));

        return $this->json($this->organizationReader->read($organization->id));
    }
}
