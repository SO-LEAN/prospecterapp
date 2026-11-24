<?php

declare(strict_types=1);

namespace App\Prospecting\Controller;

use App\Prospecting\Input\CampaignExchangeInput;
use App\Prospecting\Resource\TimelineResource;
use App\Prospecting\TimelineReader;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\Exchange\NoteCampaignExchange\NoteCampaignExchange;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The core asks Campaigns for the campaign in the member's account. Campaigns does not know a campaign in another
 * account, so this context needs no voter (ADR 0145).
 */
#[OA\Tag('Campaigns')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class NoteCampaignExchangeController
{
    public function __construct(
        private CommandBus $commandBus,
        private Identities $identities,
        private ClockInterface $clock,
        private TimelineReader $timelineReader,
        private JsonResponses $jsonResponses,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/api/campaigns/{id}/recipients/{contactId}/exchanges', name: 'api_campaigns_note_exchange', requirements: ['id' => Requirement::UUID, 'contactId' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Post(summary: 'Log an exchange with a started campaign\'s recipient, through the campaign\'s channel. The recipient is processed, and the campaign ends once nobody is left to handle')]
    #[OA\Response(response: 201, description: 'The contact\'s timeline, with the new exchange', content: new Model(type: TimelineResource::class), headers: [new OA\Header(header: 'Location', description: 'The new exchange', schema: new OA\Schema(type: 'string'))])]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown campaign, one in another account, or a contact who is not its recipient', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'The campaign is not started, or the recipient is already handled', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'A moment in the future, a missed exchange that is not a call, or a reference from another channel', content: new Model(type: ProblemResource::class))]
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

        return $this->jsonResponses->of($this->timelineReader->read($user->memberId(), $contactId), Response::HTTP_CREATED, ['Location' => $this->urlGenerator->generate('api_exchanges_show', ['id' => $exchange])]);
    }
}
