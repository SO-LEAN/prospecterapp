<?php

namespace App\Contacts\Controller;

use App\Contacts\OrganizationTimelineReader;
use App\Contacts\Resource\OrganizationTimelineResource;
use App\Prospecting\Input\OrganizationExchangeInput;
use App\Prospecting\OrganizationVoter;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\NoteOrganizationExchange\NoteOrganizationExchange;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final class NoteOrganizationExchangeController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly Identities $identities,
        private readonly ClockInterface $clock,
        private readonly OrganizationTimelineReader $organizationTimelineReader,
    ) {
    }

    #[Route('/api/organizations/{id}/exchanges', name: 'api_organizations_note_exchange', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Post(summary: 'Note a call, a message or a meeting with the organization itself, or with one of its contacts')]
    #[OA\Response(response: 201, description: 'The timeline of the organization with the new exchange', content: new Model(type: OrganizationTimelineResource::class), headers: [new OA\Header(header: 'Location', description: 'The new exchange, to join files to it', schema: new OA\Schema(type: 'string'))])]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'The person is no contact of the organization', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid exchange: unknown channel or direction, a missed exchange that is no call, a moment in the future', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization, #[MapRequestPayload] OrganizationExchangeInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $exchange = $input->exchange;
        $noted = $this->identities->next();
        $this->commandBus->dispatch(new NoteOrganizationExchange(
            prospectorId: $user->memberId(),
            id: $noted,
            organizationId: $organization->id,
            personId: $input->personId,
            channel: $exchange->channel,
            direction: $exchange->direction,
            occurredAt: $exchange->occurredAt ?? $this->clock->now(),
            text: $exchange->text,
            missed: $exchange->missed,
            executedAt: $this->clock->now(),
        ));

        return $this->json($this->organizationTimelineReader->read($user->memberId(), $organization), Response::HTTP_CREATED, ['Location' => $this->generateUrl('api_exchanges_show', ['id' => $noted])]);
    }
}
