<?php

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
     * @param string      $id          the id of the exchange
     * @param string      $channel     call, sms, email, linkedIn, malt, meeting, videoCall or other
     * @param string      $direction   inbound (the contact reached out) or outbound
     * @param string      $occurredAt  when it happened (ISO 8601); never in the future
     * @param string|null $text        what was said or written
     * @param bool        $missed      the contact did not answer or come: a call, a meeting or a video call only
     * @param bool        $madeConform declares the exchange made conform, its contact forgotten; only while its link makeConform is offered
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'update-exchange', description: 'Correct every fact of an exchange that took place, its channel, direction, moment, text and whether it was missed; its contact stays. Needs the right to manage the personal data. Answers the exchange.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, string $channel, string $direction, string $occurredAt, ?string $text = null, bool $missed = false, bool $madeConform = false): array
    {
        return $this->writes->write(
            $id,
            $this->inputs->mapped(CorrectionInput::class, ['channel' => $channel, 'direction' => $direction, 'occurredAt' => $occurredAt, 'text' => $text, 'missed' => $missed, 'madeConform' => $madeConform]),
            static fn (CorrectionInput $input, ExchangeDetail $exchange, string $memberId, \DateTimeImmutable $now): CorrectExchange => new CorrectExchange(
                prospectorId: $memberId,
                id: $exchange->id,
                channel: $input->channel,
                direction: $input->direction,
                occurredAt: $input->occurredAt ?? throw new \LogicException('The moment of a correction is validated'),
                text: $input->text,
                missed: $input->missed,
                executedAt: $now,
                madeConform: $input->madeConform,
            ),
            User::PERSONAL_DATA,
        );
    }
}
