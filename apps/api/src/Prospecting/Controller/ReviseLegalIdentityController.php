<?php

namespace App\Prospecting\Controller;

use App\Prospecting\Input\ReviseLegalIdentityInput;
use App\Prospecting\OrganizationReader;
use App\Prospecting\OrganizationVoter;
use App\Prospecting\Persistence\OrganizationRevisions;
use App\Prospecting\Resource\OrganizationResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use App\Shared\Resource\RevisionDescription;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\ReviseLegalIdentity\ReviseLegalIdentity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ReviseLegalIdentityController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly OrganizationReader $organizationReader,
        private readonly OrganizationRevisions $revisions,
    ) {
    }

    #[Route('/api/organizations/{id}/legal-identity', name: 'api_organizations_revise_legal_identity', requirements: ['id' => Requirement::UUID], methods: ['PATCH'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[OA\Patch(summary: 'Revise the legal identity (corporate name, form, type, SIRET), nothing else changes')]
    #[OA\Parameter(name: 'revision', in: 'query', description: RevisionDescription::OF_A_CHANGE)]
    #[OA\Response(response: 200, description: 'The organization with its new identity', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one that is neither mine nor prospected by mine', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'Changed since the revision sent was read', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid identity or SIRET, a SIRET another organization of the account has, or neither corporate name nor email left', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization, #[MapRequestPayload] ReviseLegalIdentityInput $input, #[CurrentUser] User $user, #[MapQueryParameter(options: ['min_range' => 0], validationFailedStatusCode: 422)] ?int $revision = null): JsonResponse
    {
        $this->commandBus->dispatch(new ReviseLegalIdentity(prospectorId: $user->memberId(), id: $organization->id, corporateName: $input->corporateName, form: $input->form, type: $input->type, executedAt: $this->clock->now(), siret: $input->siret), $this->revisions->readAt($organization->id, $revision));

        return $this->json($this->organizationReader->read($organization->id));
    }
}
