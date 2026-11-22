<?php

declare(strict_types=1);

namespace App\Prospecting\Controller;

use App\Prospecting\Input\StartProspectingInput;
use App\Prospecting\OrganizationReader;
use App\Prospecting\Resource\OrganizationResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final class StartProspectingController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly Identities $identities,
        private readonly ClockInterface $clock,
        private readonly OrganizationReader $organizationReader,
    ) {
    }

    #[Route('/api/organizations', name: 'api_organizations_start_prospecting', methods: ['POST'])]
    #[OA\Post(summary: 'Start prospecting an organization, owned by my organization')]
    #[OA\Response(response: 201, description: 'The organization now prospected', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown holding, or one that is not prospected by mine', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid organization, email, link or domain of the website already used, or neither corporate name nor email', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] StartProspectingInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $id = $this->identities->next();
        $this->commandBus->dispatch($input->commandOf($user->memberId(), $id, $this->clock->now()));

        return $this->json($this->organizationReader->read($id), Response::HTTP_CREATED, ['Location' => $this->generateUrl('api_organizations_show', ['id' => $id])]);
    }
}
