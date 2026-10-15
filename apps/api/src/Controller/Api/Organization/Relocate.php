<?php

namespace App\Controller\Api\Organization;

use App\Api\Input\AddressInput;
use App\Api\OrganizationReader;
use App\Api\Resource\OrganizationResource;
use App\Api\Resource\ProblemResource;
use App\Entity\User;
use App\Messenger\CommandBus;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Application\Organization\Relocate\Relocate as RelocateCommand;
use Prospecter\Core\Domain\Organization\OrganizationId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final class Relocate extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commands,
        private readonly OrganizationReader $organizations,
    ) {
    }

    #[Route('/api/organizations/{id}/address', name: 'api_organizations_relocate', requirements: ['id' => Requirement::UUID], methods: ['PATCH'])]
    #[OA\Patch(summary: 'Move the organization to an address (located again); an empty address removes it')]
    #[OA\Response(response: 200, description: 'The organization at its new address', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one that is neither mine nor prospected by mine', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid address', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[MapRequestPayload] AddressInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commands->dispatch(new RelocateCommand($user->getUserId(), new OrganizationId($id), $input->toAddress()));

        return $this->json($this->organizations->read($user->getUserId(), new OrganizationId($id)));
    }
}
