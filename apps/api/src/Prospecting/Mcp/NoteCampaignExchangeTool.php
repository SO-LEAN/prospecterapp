<?php

namespace App\Prospecting\Mcp;

use App\Exchanges\NotedOnceAnswers;
use App\Prospecting\Input\CampaignExchangeInput;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\NoteCampaignExchange\NoteCampaignExchange;
use Symfony\Component\Clock\ClockInterface;

/**
 * `POST /api/campaigns/{id}/recipients/{contactId}/exchanges`, for an AI leading a campaign, which notes once what it
 * sent itself (ADR 0163, 0166): the campaign is asked of Campaigns by the core, as by the endpoint (ADR 0145).
 */
#[Adapts('api_campaigns_note_exchange')]
final readonly class NoteCampaignExchangeTool
{
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private CommandBus $commandBus,
        private Identities $identities,
        private ClockInterface $clock,
        private NotedOnceAnswers $answers,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string      $id                   the id of the campaign
     * @param string      $contactId            the id of the recipient, as `destinataire-suivant` gives it
     * @param string|null $text                 the text really sent, the subject then the body of an e-mail
     * @param string|null $occurredAt           when it was sent (ISO 8601), now when left out; never in the future
     * @param string|null $messageId            the header Message-ID of the e-mail sent, as read in the mailbox
     * @param string|null $linkedInConversation the conversation of the LinkedIn message sent, its id or the address of its thread
     *
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'noter-l-envoi',
        description: 'Note the message you sent yourself to a recipient of a campaign you lead, by the channel of the campaign: the recipient is processed, the campaign ended once nobody is left to handle. Give the text really sent and the Message-ID of the e-mail, or the conversation of the LinkedIn message. Each sending is noted once in the account: noted again, the answer is its status alreadyNoted with the exchange noted first, or erasedBefore with who erased it, and nothing is noted.',
        annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false),
    )]
    public function __invoke(string $id, string $contactId, ?string $text = null, ?string $occurredAt = null, ?string $messageId = null, ?string $linkedInConversation = null): array
    {
        $member = $this->access->member();
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);
        $input = $this->inputs->mapped(CampaignExchangeInput::class, ['occurredAt' => $occurredAt, 'text' => $text, 'messageId' => $messageId, 'linkedInConversation' => $linkedInConversation]);
        $exchange = $this->identities->next();

        return $this->results->of($this->answers->of($member->memberId(), $exchange, fn () => $this->commandBus->dispatch(new NoteCampaignExchange(
            prospectorId: $member->memberId(),
            id: $exchange,
            campaignId: $id,
            personId: $contactId,
            occurredAt: $input->occurredAt ?? $this->clock->now(),
            text: $input->text,
            missed: false,
            executedAt: $this->clock->now(),
            messageId: $input->messageId,
            linkedInConversation: $input->linkedInConversation,
            noteOnce: true,
        ))));
    }
}
