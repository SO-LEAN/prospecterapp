<?php

namespace App\Contacts;

use App\Contacts\Resource\OrganizationTimelineResource;
use App\Prospecting\Input\OrganizationExchangeInput;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\NoteOrganizationExchange\NoteOrganizationExchange as NoteOrganizationExchangeCommand;
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
final class NoteOrganizationExchange extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commands,
        private readonly Identities $identities,
        private readonly ClockInterface $clock,
        private readonly OrganizationTimelineReader $timelines,
    ) {
    }

    #[Route('/api/organizations/{id}/exchanges', name: 'api_organizations_note_exchange', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[OA\Post(summary: 'Note a call, a message or a meeting with the organization itself, or with one of its contacts')]
    #[OA\Response(response: 201, description: 'The timeline of the organization with the new exchange', content: new Model(type: OrganizationTimelineResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'The person is no contact of the organization', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid exchange: unknown channel or direction, a missed exchange that is no call, a moment in the future', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[MapRequestPayload] OrganizationExchangeInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $exchange = $input->exchange;
        $this->commands->dispatch(new NoteOrganizationExchangeCommand(
            prospectorId: $user->memberId(),
            id: $this->identities->next(),
            organizationId: $id,
            personId: $input->personId,
            channel: $exchange->channel,
            direction: $exchange->direction,
            occurredAt: $exchange->occurredAt ?? $this->clock->now(),
            text: $exchange->text,
            missed: $exchange->missed,
            executedAt: $this->clock->now(),
        ));

        return $this->json($this->timelines->read($user->memberId(), $id), Response::HTTP_CREATED);
    }
}
