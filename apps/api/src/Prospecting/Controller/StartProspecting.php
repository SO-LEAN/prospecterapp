<?php

namespace App\Prospecting\Controller;

use App\Entity\User;
use App\Prospecting\Input\StartProspectingInput;
use App\Prospecting\OrganizationReader;
use App\Prospecting\Resource\OrganizationResource;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\StartProspecting\StartProspecting as StartProspectingCommand;
use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final class StartProspecting extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commands,
        private readonly OrganizationRepository $organizations,
        private readonly OrganizationReader $reader,
    ) {
    }

    #[Route('/api/organizations', name: 'api_organizations_start_prospecting', methods: ['POST'])]
    #[OA\Post(summary: 'Start prospecting an organization, owned by my organization')]
    #[OA\Response(response: 201, description: 'The organization now prospected', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown holding, or one that is not prospected by mine', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid organization, email already used, or neither corporate name nor email', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] StartProspectingInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $id = $this->organizations->nextIdentity();
        $this->commands->dispatch(new StartProspectingCommand(
            prospectorId: $user->memberId(),
            id: $id,
            legalIdentity: $input->legalIdentity->toLegalIdentity(),
            contactDetails: $input->contactDetails->toContactDetails(),
            address: $input->address?->toAddress(),
            observations: $input->observations,
            holdingId: null === $input->holdingId ? null : new OrganizationId($input->holdingId),
        ));

        return $this->json($this->reader->read($user->memberId(), $id), Response::HTTP_CREATED, ['Location' => $this->generateUrl('api_organizations_show', ['id' => $id->value])]);
    }
}
