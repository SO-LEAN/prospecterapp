<?php

namespace App\Contacts\Mcp;

use App\Exchanges\NotedOnceAnswers;
use App\Prospecting\Input\ExchangeInput;
use App\Prospecting\Input\OrganizationExchangeInput;
use App\Prospecting\OrganizationResolver;
use App\Prospecting\OrganizationVoter;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\NoteOrganizationExchange\NoteOrganizationExchange;
use Symfony\Component\Clock\ClockInterface;

/**
 * `POST /api/organizations/{id}/exchanges`, for an AI, which notes once what it reads (ADR 0163, 0166).
 */
#[Adapts('api_organizations_note_exchange')]
final readonly class NoteOrganizationExchangeTool
{
    public function __construct(
        private ToolAccess $access,
        private OrganizationResolver $organizations,
        private ToolInputs $inputs,
        private CommandBus $commandBus,
        private Identities $identities,
        private ClockInterface $clock,
        private NotedOnceAnswers $answers,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string      $id                   the id of the organization
     * @param string      $channel              call, sms, email, linkedIn, malt, meeting, videoCall or other
     * @param string      $direction            inbound (the organization reached out) or outbound
     * @param string|null $personId             the contact of the organization it was with; left out, the organization itself
     * @param string|null $occurredAt           when it happened (ISO 8601), now when left out; never in the future
     * @param string|null $text                 what was said or written; the subject then the body of an e-mail
     * @param bool        $missed               nobody answered or came: a call, a meeting or a video call only
     * @param string|null $messageId            the header Message-ID of an e-mail, as read in the mailbox
     * @param string|null $linkedInConversation the conversation of a LinkedIn message, its id or the address of its thread
     *
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'noter-un-echange-organisation',
        description: 'Note a call, a message or a meeting with an organization itself, or with one of its contacts, as read in a mailbox or a messaging: give the Message-ID of an e-mail, the conversation of a LinkedIn message. Each exchange is noted once in the account: noted again, the answer is its status alreadyNoted with the exchange noted first, or erasedBefore with who erased it, and nothing is noted.',
        annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false),
    )]
    public function __invoke(string $id, string $channel, string $direction, ?string $personId = null, ?string $occurredAt = null, ?string $text = null, bool $missed = false, ?string $messageId = null, ?string $linkedInConversation = null): array
    {
        $member = $this->access->member();
        $organization = $this->organizations->read($id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(OrganizationVoter::ACCESS, $organization);
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);
        $exchangeInput = $this->inputs->mapped(ExchangeInput::class, ['channel' => $channel, 'direction' => $direction, 'occurredAt' => $occurredAt, 'text' => $text, 'missed' => $missed, 'messageId' => $messageId, 'linkedInConversation' => $linkedInConversation]);
        $input = $this->inputs->valid(new OrganizationExchangeInput($exchangeInput, $personId));
        $exchange = $this->identities->next();

        return $this->results->of($this->answers->of($member->memberId(), $exchange, fn () => $this->commandBus->dispatch(new NoteOrganizationExchange(
            prospectorId: $member->memberId(),
            id: $exchange,
            organizationId: $organization->id,
            personId: $input->personId,
            channel: $exchangeInput->channel,
            direction: $exchangeInput->direction,
            occurredAt: $exchangeInput->occurredAt ?? $this->clock->now(),
            text: $exchangeInput->text,
            missed: $exchangeInput->missed,
            executedAt: $this->clock->now(),
            messageId: $exchangeInput->messageId,
            linkedInConversation: $exchangeInput->linkedInConversation,
            noteOnce: true,
            byAnAI: true,
        ))));
    }
}
