<?php

namespace App\Prospecting\Controller;

use App\Prospecting\Input\CampaignExchangeInput;
use App\Prospecting\Resource\TimelineResource;
use App\Prospecting\TimelineReader;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\NoteCampaignExchange\NoteCampaignExchange;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The campaign is asked of Campaigns by the core, in the account of the member: one of another account is unknown
 * there, without a voter of this context (ADR 0145).
 */
#[OA\Tag('Campaigns')]
#[IsGranted('ROLE_PROSPECTOR')]
final class NoteCampaignExchangeController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly Identities $identities,
        private readonly ClockInterface $clock,
        private readonly TimelineReader $timelineReader,
    ) {
    }

    #[Route('/api/campaigns/{id}/recipients/{contactId}/exchanges', name: 'api_campaigns_note_exchange', requirements: ['id' => Requirement::UUID, 'contactId' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Post(summary: 'Note the exchange had with a recipient of a started campaign, by its channel: the recipient is processed, the campaign ended once nobody is left to handle')]
    #[OA\Response(response: 201, description: 'The timeline of the contact with the new exchange', content: new Model(type: TimelineResource::class), headers: [new OA\Header(header: 'Location', description: 'The new exchange', schema: new OA\Schema(type: 'string'))])]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown campaign or of another account, or no recipient of it', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'The campaign is not started, or the recipient is handled already', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'A moment in the future, a missed exchange that is no call, or a reference of another channel', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, string $contactId, #[MapRequestPayload] CampaignExchangeInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $exchange = $this->identities->next();
        $this->commandBus->dispatch(new NoteCampaignExchange(
            prospectorId: $user->memberId(),
            id: $exchange,
            campaignId: $id,
            personId: $contactId,
            occurredAt: $input->occurredAt ?? $this->clock->now(),
            text: $input->text,
            missed: $input->missed,
            executedAt: $this->clock->now(),
            messageId: $input->messageId,
            linkedInConversation: $input->linkedInConversation,
        ));

        return $this->json($this->timelineReader->read($user->memberId(), $contactId), Response::HTTP_CREATED, ['Location' => $this->generateUrl('api_exchanges_show', ['id' => $exchange])]);
    }
}
