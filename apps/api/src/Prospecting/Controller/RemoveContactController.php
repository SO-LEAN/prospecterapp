<?php

namespace App\Prospecting\Controller;

use App\Prospecting\OrganizationReader;
use App\Prospecting\OrganizationVoter;
use App\Prospecting\Resource\OrganizationResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\RemoveContact\RemoveContact;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final class RemoveContactController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly OrganizationReader $organizationReader,
    ) {
    }

    #[Route('/api/organizations/{id}/contacts/{personId}', name: 'api_organizations_remove_contact', requirements: ['id' => Requirement::UUID, 'personId' => Requirement::UUID], methods: ['DELETE'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Delete(summary: 'Detach a contact from the organization; the person stays among my contacts')]
    #[OA\Response(response: 200, description: 'The organization without this contact', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'The person is no contact of the organization', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization, string $personId, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new RemoveContact($user->memberId(), $organization->id, $personId, $this->clock->now()));

        return $this->json($this->organizationReader->read($organization->id));
    }
}
