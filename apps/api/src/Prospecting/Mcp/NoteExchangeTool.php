<?php

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
 * `POST /api/contacts/{id}/exchanges`, for an AI, which notes once what it reads (ADR 0163, 0166).
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
     * @param string|null $occurredAt           when it happened (ISO 8601), now when left out; never in the future
     * @param string|null $text                 what was said or written; the subject then the body of an e-mail
     * @param bool        $missed               the contact did not answer or come: a call, a meeting or a video call only
     * @param string|null $messageId            the header Message-ID of an e-mail, as read in the mailbox
     * @param string|null $linkedInConversation the conversation of a LinkedIn message, its id or the address of its thread
     *
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'noter-un-echange',
        description: 'Note a call, a message or a meeting with a contact, as read in a mailbox or a messaging: give the Message-ID of an e-mail, the conversation of a LinkedIn message. Each exchange is noted once in the account: noted again, the answer is its status alreadyNoted with the exchange noted first, or erasedBefore with who erased it, and nothing is noted.',
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
        ))));
    }
}
