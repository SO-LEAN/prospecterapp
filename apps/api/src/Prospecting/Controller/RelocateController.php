<?php

declare(strict_types=1);

namespace App\Prospecting\Controller;

use App\Prospecting\OrganizationReader;
use App\Prospecting\OrganizationVoter;
use App\Prospecting\Persistence\OrganizationRevisions;
use App\Prospecting\Resource\OrganizationResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Input\AddressInput;
use App\Shared\Resource\ProblemResource;
use App\Shared\Resource\RevisionDescription;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Relocate\Relocate;
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
final class RelocateController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly OrganizationReader $organizationReader,
        private readonly OrganizationRevisions $revisions,
    ) {
    }

    #[Route('/api/organizations/{id}/address', name: 'api_organizations_relocate', requirements: ['id' => Requirement::UUID], methods: ['PATCH'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[OA\Patch(summary: 'Move the organization to an address, and locate it again. An empty address removes the address')]
    #[OA\Parameter(name: 'revision', in: 'query', description: RevisionDescription::OF_A_CHANGE)]
    #[OA\Response(response: 200, description: 'The organization at its new address', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one that my account neither owns nor prospects', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'Changed since the revision sent was read', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid address', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization, #[MapRequestPayload] AddressInput $input, #[CurrentUser] User $user, #[MapQueryParameter(options: ['min_range' => 0], validationFailedStatusCode: 422)] ?int $revision = null): JsonResponse
    {
        $this->commandBus->dispatch(new Relocate(prospectorId: $user->memberId(), id: $organization->id, street: $input->street, postalCode: $input->postalCode, city: $input->city, country: $input->country, executedAt: $this->clock->now()), $this->revisions->readAt($organization->id, $revision));

        return $this->json($this->organizationReader->read($organization->id));
    }
}
