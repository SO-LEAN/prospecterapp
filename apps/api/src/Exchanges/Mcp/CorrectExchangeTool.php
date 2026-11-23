<?php

declare(strict_types=1);

namespace App\Exchanges\Mcp;

use App\Exchanges\Input\CorrectionInput;
use App\Security\User;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\CorrectExchange\CorrectExchange;

/**
 * `PATCH /api/exchanges/{id}/facts`, for an AI (ADR 0163).
 */
#[Adapts('api_exchanges_correct')]
final readonly class CorrectExchangeTool
{
    public function __construct(private ExchangeWrites $writes, private ToolInputs $inputs)
    {
    }

    /**
     * @param string      $id         the exchange's id
     * @param string      $channel    call, sms, email, linkedIn, malt, meeting, videoCall or other
     * @param string      $direction  inbound (the contact reached out) or outbound
     * @param string      $occurredAt when it happened (ISO 8601), never in the future
     * @param string|null $text       what was said or written
     * @param bool        $missed     the contact did not answer or did not come. Only a call, a meeting or a video call can be missed
     * @param bool        $redacted   declares the exchange redacted once its contact is forgotten. Allowed only while its `redact` link is offered
     * @param ?int        $revision   the exchange's `revision` as you read it. If the exchange changed since, the change is refused (changedMeanwhile): read it again
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'update-exchange', description: 'Correct all the facts of an exchange that took place: its channel, direction, moment, text, and whether it was missed. Its contact stays the same. Needs the right to manage personal data. Returns the exchange.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, string $channel, string $direction, string $occurredAt, ?string $text = null, bool $missed = false, bool $redacted = false, ?int $revision = null): array
    {
        return $this->writes->writeOnRevision(
            $id,
            $revision,
            $this->inputs->mapped(CorrectionInput::class, ['channel' => $channel, 'direction' => $direction, 'occurredAt' => $occurredAt, 'text' => $text, 'missed' => $missed, 'redacted' => $redacted]),
            static fn (CorrectionInput $input, ExchangeDetail $exchange, string $memberId, \DateTimeImmutable $now): CorrectExchange => new CorrectExchange(
                prospectorId: $memberId,
                id: $exchange->id,
                channel: $input->channel,
                direction: $input->direction,
                occurredAt: $input->occurredAt ?? throw new \LogicException('The moment of a correction is validated'),
                text: $input->text,
                missed: $input->missed,
                executedAt: $now,
                madeConform: $input->redacted,
            ),
            User::PERSONAL_DATA,
        );
    }
}
