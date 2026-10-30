<?php

namespace App\Prospecting\Controller;

use App\Prospecting\Input\ContactInput;
use App\Prospecting\OrganizationReader;
use App\Prospecting\OrganizationVoter;
use App\Prospecting\Resource\OrganizationResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\AddContact\AddContact;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
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
final class AddContactController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly OrganizationReader $organizationReader,
    ) {
    }

    #[Route('/api/organizations/{id}/contacts', name: 'api_organizations_add_contact', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Post(summary: 'Attach a person my organization prospects as a contact of the organization')]
    #[OA\Response(response: 200, description: 'The organization with its new contact', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization or contact, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'The person is a contact of the organization already', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid person id', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization, #[MapRequestPayload] ContactInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new AddContact($user->memberId(), $organization->id, $input->personId, $this->clock->now()));

        return $this->json($this->organizationReader->read($organization->id));
    }
}
