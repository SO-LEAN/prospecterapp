<?php

declare(strict_types=1);

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
use Prospecter\Core\Prospecting\Application\SendingNoted\KeptSendingNoted;
use Prospecter\Core\Prospecting\Application\SendingNoted\SendingNoted;
use Prospecter\Core\Prospecting\Application\SendingNoted\SendingNotedOutput;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Component\Clock\ClockInterface;

/**
 * `POST /api/campaigns/{id}/recipients/{contactId}/exchanges`, for an AI that leads a campaign. The AI logs each
 * message it sent itself, once (ADR 0163, 0166). As for the endpoint, the core asks Campaigns for the campaign
 * (ADR 0145).
 */
#[Adapts('api_campaigns_note_exchange')]
final readonly class NoteCampaignExchangeTool
{
    /**
     * @param QueryHandler&callable(SendingNoted, SendingNotedOutput): void $sendingNoted
     */
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private CommandBus $commandBus,
        private Identities $identities,
        private ClockInterface $clock,
        private NotedOnceAnswers $answers,
        private ToolResults $results,
        private QueryHandler $sendingNoted,
    ) {
    }

    /**
     * @param string      $id                   the id of the campaign
     * @param string      $contactId            the id of the recipient, as `get-next-recipient` gives it
     * @param string|null $text                 the exact text sent. For an e-mail, its subject then its body
     * @param string|null $occurredAt           when it was sent (ISO 8601), never in the future. Now when left out
     * @param string|null $messageId            the sent e-mail's Message-ID header, as read in the mailbox
     * @param string|null $linkedInConversation the sent LinkedIn message's conversation: its id or its thread's address
     *
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'log-sent-message',
        description: 'Log a message that you sent yourself to a recipient of a campaign you lead, through the campaign\'s channel. The recipient is then processed. The campaign ends once nobody is left to handle. Give the exact text sent, and the e-mail\'s Message-ID or the LinkedIn message\'s conversation. Each sending is logged once in the account. When the import of your member\'s mailbox logged it first, outside the campaign, it joins the campaign and the answer\'s status is `noted`. When it is already logged for the campaign, nothing is logged: the answer\'s status is `alreadyNoted` with the exchange logged first, or `erasedBefore` with who erased it.',
        annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false),
    )]
    public function __invoke(string $id, string $contactId, ?string $text = null, ?string $occurredAt = null, ?string $messageId = null, ?string $linkedInConversation = null): array
    {
        $member = $this->access->member();
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);
        $input = $this->inputs->mapped(CampaignExchangeInput::class, ['occurredAt' => $occurredAt, 'text' => $text, 'messageId' => $messageId, 'linkedInConversation' => $linkedInConversation]);
        $exchange = $this->identities->next();
        $sending = new SendingNoted(
            prospectorId: $member->memberId(),
            campaignId: $id,
            personId: $contactId,
            occurredAt: $input->occurredAt ?? $this->clock->now(),
            text: $input->text,
            messageId: $input->messageId,
            linkedInConversation: $input->linkedInConversation,
        );

        return $this->results->of($this->answers->ofNoted($member->memberId(), fn (): string => $this->exchangeNotedFor($sending) ?? $exchange, fn () => $this->commandBus->dispatch(new NoteCampaignExchange(
            prospectorId: $member->memberId(),
            id: $exchange,
            campaignId: $id,
            personId: $contactId,
            occurredAt: $sending->occurredAt,
            text: $input->text,
            missed: false,
            executedAt: $this->clock->now(),
            messageId: $input->messageId,
            linkedInConversation: $input->linkedInConversation,
            noteOnce: true,
            byAnAI: true,
        ))));
    }

    /**
     * The exchange that the sending was logged as: the one just logged, or the one logged first that joined the
     * campaign (ADR 0195).
     */
    private function exchangeNotedFor(SendingNoted $sending): ?string
    {
        $kept = new KeptSendingNoted();
        ($this->sendingNoted)($sending, $kept);

        return $kept->exchangeId;
    }
}
