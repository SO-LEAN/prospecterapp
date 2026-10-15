<?php

namespace App\Controller\Api\Organization;

use App\Api\Input\StartProspectingInput;
use App\Api\OrganizationReader;
use App\Api\Resource\OrganizationResource;
use App\Api\Resource\ProblemResource;
use App\Entity\User;
use App\Messenger\CommandBus;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Application\Organization\StartProspecting\StartProspecting as StartProspectingCommand;
use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
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
            prospectorId: $user->getUserId(),
            id: $id,
            legalIdentity: $input->legalIdentity->toLegalIdentity(),
            contactDetails: $input->contactDetails->toContactDetails(),
            address: $input->address?->toAddress(),
            observations: $input->observations,
            holdingId: null === $input->holdingId ? null : new OrganizationId($input->holdingId),
        ));

        return $this->json($this->reader->read($user->getUserId(), $id), Response::HTTP_CREATED, ['Location' => $this->generateUrl('api_organizations_show', ['id' => $id->value])]);
    }
}
