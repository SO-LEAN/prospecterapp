<?php

namespace App\Prospecting\Controller;

use App\Prospecting\Input\ExchangeInput;
use App\Prospecting\Resource\TimelineResource;
use App\Prospecting\TimelineReader;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\NoteExchange\NoteExchange;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final class NoteExchangeController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly Identities $identities,
        private readonly ClockInterface $clock,
        private readonly TimelineReader $timelineReader,
    ) {
    }

    #[Route('/api/contacts/{id}/exchanges', name: 'api_contacts_note_exchange', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Post(summary: 'Note a call, a message or a meeting with the contact, kept for the organizations the contact is part of')]
    #[OA\Response(response: 201, description: 'The timeline of the contact with the new exchange', content: new Model(type: TimelineResource::class), headers: [new OA\Header(header: 'Location', description: 'The new exchange, to join files to it', schema: new OA\Schema(type: 'string'))])]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown contact, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid exchange: unknown channel or direction, a missed exchange that is no call, a moment in the future', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[MapRequestPayload] ExchangeInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $exchange = $this->identities->next();
        $this->commandBus->dispatch(new NoteExchange(
            prospectorId: $user->memberId(),
            id: $exchange,
            personId: $id,
            channel: $input->channel,
            direction: $input->direction,
            occurredAt: $input->occurredAt ?? $this->clock->now(),
            text: $input->text,
            missed: $input->missed,
            executedAt: $this->clock->now(),
        ));

        return $this->json($this->timelineReader->read($user->memberId(), $id), Response::HTTP_CREATED, ['Location' => $this->generateUrl('api_exchanges_show', ['id' => $exchange])]);
    }
}
