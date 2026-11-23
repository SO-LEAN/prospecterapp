<?php

declare(strict_types=1);

namespace App\Prospecting\Mcp;

use App\Exchanges\NotedOnceAnswers;
use App\Prospecting\Input\ExchangeInput;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\NoteExchange\NoteExchange;
use Symfony\Component\Clock\ClockInterface;

/**
 * `POST /api/contacts/{id}/exchanges`, for an AI. The AI logs each exchange it reads, once (ADR 0163, 0166).
 */
#[Adapts('api_contacts_note_exchange')]
final readonly class NoteExchangeTool
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
     * @param string      $id                   the id of the contact
     * @param string      $channel              call, sms, email, linkedIn, malt, meeting, videoCall or other
     * @param string      $direction            inbound (the contact reached out) or outbound
     * @param string|null $occurredAt           when it happened (ISO 8601), never in the future. Now when left out
     * @param string|null $text                 what was said or written. For an e-mail, its subject then its body
     * @param bool        $missed               true when the contact did not answer or come. A call, a meeting or a video call only
     * @param string|null $messageId            an e-mail's Message-ID header, as read in the mailbox
     * @param string|null $linkedInConversation a LinkedIn message's conversation: its id or its thread's address
     *
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'log-contact-exchange',
        description: 'Log a call, a message or a meeting with a contact, as read in a mailbox or a messaging app. Give an e-mail\'s Message-ID, or a LinkedIn message\'s conversation. Each exchange is logged once in the account. When it is already logged, nothing is logged: the answer\'s status is `alreadyNoted` with the exchange logged first, or `erasedBefore` with who erased it.',
        annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false),
    )]
    public function __invoke(string $id, string $channel, string $direction, ?string $occurredAt = null, ?string $text = null, bool $missed = false, ?string $messageId = null, ?string $linkedInConversation = null): array
    {
        $member = $this->access->member();
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);
        $input = $this->inputs->mapped(ExchangeInput::class, ['channel' => $channel, 'direction' => $direction, 'occurredAt' => $occurredAt, 'text' => $text, 'missed' => $missed, 'messageId' => $messageId, 'linkedInConversation' => $linkedInConversation]);
        $exchange = $this->identities->next();

        return $this->results->of($this->answers->of($member->memberId(), $exchange, fn () => $this->commandBus->dispatch(new NoteExchange(
            prospectorId: $member->memberId(),
            id: $exchange,
            personId: $id,
            channel: $input->channel,
            direction: $input->direction,
            occurredAt: $input->occurredAt ?? $this->clock->now(),
            text: $input->text,
            missed: $input->missed,
            executedAt: $this->clock->now(),
            messageId: $input->messageId,
            linkedInConversation: $input->linkedInConversation,
            noteOnce: true,
            byAnAI: true,
        ))));
    }
}
