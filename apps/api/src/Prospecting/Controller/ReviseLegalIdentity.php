<?php

namespace App\Prospecting\Controller;

use App\Prospecting\OrganizationReader;
use App\Prospecting\Resource\OrganizationResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Input\LegalIdentityInput;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\ReviseLegalIdentity\ReviseLegalIdentity as ReviseLegalIdentityCommand;
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
final class ReviseLegalIdentity extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commands,
        private readonly ClockInterface $clock,
        private readonly OrganizationReader $organizations,
    ) {
    }

    #[Route('/api/organizations/{id}/legal-identity', name: 'api_organizations_revise_legal_identity', requirements: ['id' => Requirement::UUID], methods: ['PATCH'])]
    #[OA\Patch(summary: 'Revise the legal identity (corporate name, form, type), nothing else changes')]
    #[OA\Response(response: 200, description: 'The organization with its new identity', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one that is neither mine nor prospected by mine', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid identity, or neither corporate name nor email left', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[MapRequestPayload] LegalIdentityInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commands->dispatch(new ReviseLegalIdentityCommand($user->memberId(), $id, $input->corporateName, $input->form, $input->type, $this->clock->now()));

        return $this->json($this->organizations->read($user->memberId(), $id));
    }
}
