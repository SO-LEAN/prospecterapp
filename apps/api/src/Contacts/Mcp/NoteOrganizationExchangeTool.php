<?php

declare(strict_types=1);

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
 * `POST /api/organizations/{id}/exchanges`, for an AI. The AI logs what it reads once (ADR 0163, 0166).
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
     * @param string      $id                   the organization's id
     * @param string      $channel              call, sms, email, linkedIn, malt, meeting, videoCall or other
     * @param string      $direction            inbound (the organization reached out) or outbound
     * @param string|null $personId             the organization's contact it was with. Leave it out for the organization itself
     * @param string|null $occurredAt           when it happened (ISO 8601), never in the future. It is now when left out
     * @param string|null $text                 what was said or written. For an e-mail, the subject then the body
     * @param bool        $missed               nobody answered or came. Only for a call, a meeting or a video call
     * @param string|null $messageId            an e-mail's Message-ID header, as read in the mailbox
     * @param string|null $linkedInConversation a LinkedIn message's conversation: its id or its thread's address
     *
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'log-organization-exchange',
        description: 'Log a call, a message or a meeting with an organization itself, or with one of its contacts, as read in a mailbox or a messaging app. Give an e-mail\'s Message-ID, or a LinkedIn message\'s conversation. The account logs each exchange once. When you log it again, nothing is logged. The answer\'s status is then alreadyNoted, with the exchange logged first, or erasedBefore, with who erased it.',
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
