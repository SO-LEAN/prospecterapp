<?php

namespace App\Prospecting\Controller;

use App\Prospecting\Input\AddressInput;
use App\Prospecting\OrganizationReader;
use App\Prospecting\Resource\OrganizationResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\Relocate\Relocate as RelocateCommand;
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
final class Relocate extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commands,
        private readonly ClockInterface $clock,
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
        $this->commands->dispatch(new RelocateCommand($user->memberId(), $id, $input->street, $input->postalCode, $input->city, $input->country, $this->clock->now()));

        return $this->json($this->organizations->read($user->memberId(), $id));
    }
}
